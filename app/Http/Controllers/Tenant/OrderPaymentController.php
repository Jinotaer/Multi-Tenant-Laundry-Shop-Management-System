<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PayMongoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderPaymentController extends Controller
{
    public function __construct(
        protected PayMongoService $paymongo,
    ) {}

    /**
     * Create or reuse a PayMongo checkout session for an order payment.
     */
    public function checkout(Request $request, Order $order): RedirectResponse
    {
        abort_unless(tenant()->hasFeature('online_payments'), 403);

        $this->authorizeOrderAccess($order);

        if (! $order->canBePaidOnline()) {
            return $this->redirectToOrder($order, 'error', 'This order is no longer eligible for online payment.');
        }

        $existingPayments = Payment::query()
            ->where('tenant_id', tenant()->id)
            ->where('payment_type', 'order')
            ->where('tenant_order_id', $order->id)
            ->where('status', 'pending')
            ->whereNotNull('paymongo_checkout_id')
            ->get();

        foreach ($existingPayments as $existingPayment) {
            try {
                $sessionStatus = $this->paymongo->getCheckoutSessionStatus($existingPayment->paymongo_checkout_id);

                if ($sessionStatus['status'] === 'succeeded' || $sessionStatus['link_status'] === 'paid') {
                    $this->markOrderAsPaid($order, $existingPayment, $sessionStatus);

                    return $this->redirectToOrder($order, 'success', 'Online payment completed successfully.');
                }

                if ($sessionStatus['link_status'] === 'active' && $sessionStatus['checkout_url']) {
                    return redirect()->away($sessionStatus['checkout_url']);
                }
            } catch (\Throwable) {
                continue;
            }
        }

        $payment = Payment::create([
            'tenant_id' => tenant()->id,
            'payment_type' => 'order',
            'tenant_order_id' => $order->id,
            'amount' => $order->outstandingBalance(),
            'currency' => 'PHP',
            'status' => 'pending',
            'description' => 'Order payment - '.$order->order_number,
            'customer_name' => $order->customer?->name,
            'customer_email' => $order->customer?->email,
            'metadata' => [
                'tenant_id' => tenant()->id,
                'order_id' => $order->id,
                'payment_id' => null,
            ],
        ]);

        $baseUrl = $request->getSchemeAndHttpHost();

        try {
            $checkout = $this->paymongo->createCheckoutSession([
                'amount' => (int) round((float) $order->outstandingBalance() * 100),
                'currency' => 'PHP',
                'description' => 'Order payment - '.$order->order_number,
                'success_url' => $baseUrl.route('tenant.order-payments.success', [
                    'order' => $order,
                    'payment_id' => $payment->id,
                ], absolute: false),
                'cancel_url' => $baseUrl.$this->resolveReturnPath($order),
                'metadata' => [
                    'tenant_id' => tenant()->id,
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                ],
            ]);

            $payment->update([
                'paymongo_checkout_id' => $checkout['id'],
                'checkout_url' => $checkout['checkout_url'],
                'metadata' => array_merge($payment->metadata ?? [], ['payment_id' => $payment->id]),
            ]);

            return redirect()->away($checkout['checkout_url']);
        } catch (\Throwable) {
            $payment->update(['status' => 'failed']);

            return $this->redirectToOrder($order, 'error', 'Unable to create an online checkout session right now.');
        }
    }

    /**
     * Handle the return from PayMongo after a customer completes checkout.
     */
    public function success(Request $request, Order $order): RedirectResponse
    {
        abort_unless(tenant()->hasFeature('online_payments'), 403);

        $this->authorizeOrderAccess($order);

        $payment = Payment::query()
            ->where('id', $request->query('payment_id'))
            ->where('tenant_id', tenant()->id)
            ->where('payment_type', 'order')
            ->where('tenant_order_id', $order->id)
            ->first();

        if (! $payment) {
            return $this->redirectToOrder($order, 'error', 'Unable to locate the payment record for this order.');
        }

        if ($payment->paymongo_checkout_id && $payment->isPending()) {
            try {
                $sessionStatus = $this->paymongo->getCheckoutSessionStatus($payment->paymongo_checkout_id);

                if ($sessionStatus['status'] === 'succeeded' || $sessionStatus['link_status'] === 'paid') {
                    $this->markOrderAsPaid($order, $payment, $sessionStatus);
                }
            } catch (\Throwable) {
                // The webhook will reconcile delayed payments.
            }
        }

        if ($order->fresh()?->isPaid()) {
            return $this->redirectToOrder($order, 'success', 'Online payment completed successfully.');
        }

        return $this->redirectToOrder($order, 'error', 'Payment is still pending confirmation. Please refresh again in a moment.');
    }

    /**
     * Mark the order and payment as paid.
     *
     * @param  array<string, mixed>  $sessionStatus
     */
    private function markOrderAsPaid(Order $order, Payment $payment, array $sessionStatus): void
    {
        $payment->update([
            'status' => 'paid',
            'payment_method' => $sessionStatus['payment_method'],
            'paymongo_payment_id' => $sessionStatus['payment_id'],
            'paid_at' => now(),
        ]);

        $order->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    /**
     * Ensure the authenticated user can act on the order.
     */
    private function authorizeOrderAccess(Order $order): void
    {
        $user = auth()->user();

        if ($user->isOwner() || $user->isStaff()) {
            return;
        }

        if ($user->isCustomer()) {
            $customer = $user instanceof Customer
                ? $user
                : Customer::query()->where('email', $user->email)->first();

            abort_unless($customer && $customer->id === $order->customer_id, 403);

            return;
        }

        abort(403);
    }

    /**
     * Redirect to the most appropriate order detail page for the current user.
     */
    private function redirectToOrder(Order $order, string $flashKey, string $message): RedirectResponse
    {
        $route = auth()->user()->isCustomer() && tenant()->hasFeature('customer_portal')
            ? 'tenant.portal.show'
            : 'tenant.orders.show';

        return redirect()->route($route, $order)->with($flashKey, $message);
    }

    /**
     * Resolve the relative return path for the current user.
     */
    private function resolveReturnPath(Order $order): string
    {
        return route(
            auth()->user()->isCustomer() && tenant()->hasFeature('customer_portal')
                ? 'tenant.portal.show'
                : 'tenant.orders.show',
            $order,
            absolute: false,
        );
    }
}
