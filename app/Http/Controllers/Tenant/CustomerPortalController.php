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

        $activeOrders = collect();
        $orderHistory = collect();

        if ($customer) {
            $activeOrders = Order::where('customer_id', $customer->id)
                ->with('service')
                ->whereNotIn('status', ['delivered'])
                ->latest()
                ->get();

            $orderHistory = Order::where('customer_id', $customer->id)
                ->with('service')
                ->when($request->search, fn ($q) => $q->where('order_number', 'like', "%{$request->search}%"))
                ->latest()
                ->paginate(10)
                ->withQueryString();
        }

        return view('tenant.portal.index', compact('user', 'customer', 'activeOrders', 'orderHistory'));
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
