<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PayMongoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        protected PayMongoService $paymongo,
    ) {}

    /**
     * Show the payment page for premium plan activation.
     */
    public function show(): View|RedirectResponse
    {
        $tenant = tenant();
        $tenant->load('subscriptionPlan');

        // Already paid — go to dashboard
        if ($tenant->is_paid) {
            return redirect()->route('tenant.dashboard');
        }

        // On free plan with trial — go to dashboard
        if ($tenant->isOnTrial() && $tenant->subscriptionPlan?->isFree()) {
            return redirect()->route('tenant.dashboard');
        }

        return view('tenant.payment', [
            'tenant' => $tenant,
            'plan' => $tenant->subscriptionPlan,
            'shopName' => $tenant->data['shop_name'] ?? $tenant->id,
        ]);
    }

    /**
     * Create a PayMongo checkout session and redirect to payment.
     *
     * Before creating a new session, this checks all existing pending payments:
     * — If any has already been paid, activate the tenant immediately.
     * — If any has an active (unexpired) checkout link, reuse it.
     * — Otherwise, create a fresh checkout session.
     */
    public function checkout(Request $request): RedirectResponse
    {
        $tenant = tenant();
        $tenant->load('subscriptionPlan');
        $plan = $tenant->subscriptionPlan;

        if (! $plan || $plan->isFree()) {
            return redirect()->route('tenant.dashboard')
                ->with('error', 'No payment required for free plans.');
        }

        if ($tenant->is_paid) {
            return redirect()->route('tenant.dashboard');
        }

        // Check existing pending payments against PayMongo API
        $existingPayments = Payment::where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->whereNotNull('paymongo_checkout_id')
            ->get();

        $activeCheckoutUrl = null;

        foreach ($existingPayments as $existingPayment) {
            try {
                $sessionStatus = $this->paymongo->getCheckoutSessionStatus(
                    $existingPayment->paymongo_checkout_id
                );

                // Already paid via a previous checkout — activate tenant
                if ($sessionStatus['status'] === 'succeeded' || $sessionStatus['link_status'] === 'paid') {
                    $existingPayment->update([
                        'status' => 'paid',
                        'payment_method' => $sessionStatus['payment_method'],
                        'paymongo_payment_id' => $sessionStatus['payment_id'],
                        'paid_at' => now(),
                    ]);

                    $tenant->update([
                        'is_paid' => true,
                        'is_enabled' => true,
                    ]);

                    return redirect()->route('tenant.dashboard');
                }

                // Checkout link is still active — reuse it
                if ($sessionStatus['link_status'] === 'active' && $sessionStatus['checkout_url']) {
                    $activeCheckoutUrl = $sessionStatus['checkout_url'];
                }
            } catch (\Exception) {
                // If API call fails for this payment, skip and continue
                continue;
            }
        }

        // Redirect to the still-active checkout link
        if ($activeCheckoutUrl !== null) {
            return redirect()->away($activeCheckoutUrl);
        }

        // No existing active/paid session — create a new checkout
        $amountInCentavos = (int) ($plan->price * 100);
        $shopName = $tenant->data['shop_name'] ?? $tenant->id;
        $domain = $tenant->domains->first()?->domain ?? "{$tenant->id}.localhost";

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'amount' => $plan->price,
            'currency' => 'PHP',
            'status' => 'pending',
            'description' => "{$plan->name} Plan — {$shopName}",
        ]);

        try {
            $checkout = $this->paymongo->createCheckoutSession([
                'amount' => $amountInCentavos,
                'currency' => 'PHP',
                'description' => "{$plan->name} Plan — {$shopName}",
                'success_url' => "http://{$domain}:8000/payment/success?payment_id={$payment->id}",
                'cancel_url' => "http://{$domain}:8000/payment",
                'metadata' => [
                    'tenant_id' => $tenant->id,
                    'payment_id' => $payment->id,
                    'plan_id' => $plan->id,
                ],
            ]);

            $payment->update([
                'paymongo_checkout_id' => $checkout['id'],
            ]);

            return redirect()->away($checkout['checkout_url']);
        } catch (\Exception $e) {
            $payment->update(['status' => 'failed']);

            return back()->with('error', 'Unable to create checkout session. Please try again.');
        }
    }

    /**
     * Handle successful payment return from PayMongo.
     */
    public function success(Request $request): View|RedirectResponse
    {
        $tenant = tenant();
        $paymentId = $request->query('payment_id');

        if (! $paymentId) {
            return redirect()->route('tenant.payment.show');
        }

        $payment = Payment::where('id', $paymentId)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (! $payment) {
            return redirect()->route('tenant.payment.show');
        }

        // Verify payment with PayMongo if we have a checkout ID
        if ($payment->paymongo_checkout_id && $payment->isPending()) {
            try {
                $sessionStatus = $this->paymongo->getCheckoutSessionStatus(
                    $payment->paymongo_checkout_id
                );

                if ($sessionStatus['status'] === 'succeeded'
                    || $sessionStatus['link_status'] === 'active'
                    || $sessionStatus['link_status'] === 'paid'
                ) {
                    $payment->update([
                        'status' => 'paid',
                        'payment_method' => $sessionStatus['payment_method'],
                        'paymongo_payment_id' => $sessionStatus['payment_id'],
                        'paid_at' => now(),
                    ]);

                    $tenant->update([
                        'is_paid' => true,
                        'is_enabled' => true,
                    ]);
                }
            } catch (\Exception) {
                // Verification failed — webhook will handle it
            }
        }

        if ($payment->isPaid() || $tenant->is_paid) {
            return view('tenant.payment-success', [
                'tenant' => $tenant,
                'payment' => $payment,
                'plan' => $tenant->subscriptionPlan,
            ]);
        }

        // Payment still pending — show waiting page
        return view('tenant.payment-pending', [
            'tenant' => $tenant,
            'payment' => $payment,
        ]);
    }
}
