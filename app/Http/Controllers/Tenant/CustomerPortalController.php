<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerPortalController extends Controller
{
    /**
     * Customer dashboard — show active orders and history.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $customer = $user instanceof Customer
            ? $user
            : Customer::query()->where('email', $user->email)->first();
        $completedStatuses = array_values(array_unique([
            Order::terminalStatusForPlan(),
            'delivered',
        ]));

        $activeOrders = collect();
        $orderHistory = collect();
        $loyalty = null;

        if ($customer) {
            if (tenant()->hasFeature('customer_loyalty')) {
                $loyalty = $customer->loyalty()->firstOrCreate(
                    [],
                    [
                        'points' => 0,
                        'stamps' => 0,
                        'tier' => 'bronze',
                        'lifetime_spent' => 0,
                    ]
                );
            }

            $activeOrders = Order::where('customer_id', $customer->id)
                ->with('service')
                ->whereNotIn('status', $completedStatuses)
                ->latest()
                ->get();

            $orderHistory = Order::where('customer_id', $customer->id)
                ->with('service')
                ->when($request->search, fn ($q) => $q->where('order_number', 'like', "%{$request->search}%"))
                ->latest()
                ->paginate(10)
                ->withQueryString();
        }

        return view('tenant.portal.index', compact('user', 'customer', 'activeOrders', 'orderHistory', 'loyalty'));
    }

    /**
     * Show a specific order detail.
     */
    public function show(Order $order): View
    {
        $user = auth()->user();
        $customer = $user instanceof Customer
            ? $user
            : Customer::query()->where('email', $user->email)->first();

        abort_unless($customer && $order->customer_id === $customer->id, 403);

        $order->load(['customer', 'service']);

        return view('tenant.portal.show', compact('order'));
    }
}
