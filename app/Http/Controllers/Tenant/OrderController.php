<?php

namespace App\Http\Controllers\Tenant;

use App\Events\OrderStatusChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\OrderRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * Display a listing of all orders.
     */
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->with(['customer', 'service'])
            ->when($request->search, fn ($q) => $q->where('order_number', 'like', "%{$request->search}%")
                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$request->search}%")))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->payment, fn ($q) => $q->where('payment_status', $request->payment))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $statuses = Order::statusLabelsForPlan();

        return view('tenant.orders.index', compact('orders', 'statuses'));
    }

    /**
     * Show the form for creating a new order.
     */
    public function create(): View
    {
        $customers = Customer::orderBy('name')->get();
        $services = Service::active()->orderBy('sort_order')->orderBy('name')->get();
        $statuses = Order::statusLabelsForPlan();

        return view('tenant.orders.create', compact('customers', 'services', 'statuses'));
    }

    /**
     * Store a newly created order.
     */
    public function store(OrderRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['order_number'] = Order::generateOrderNumber();
        $data['payment_status'] = 'unpaid';
        $data = $this->prepareOrderData($data);

        Order::create($data);

        return redirect()->route('tenant.orders.index')
            ->with('success', "Order {$data['order_number']} created successfully.");
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order): View
    {
        $order->load(['customer', 'service']);
        $statuses = Order::statusLabelsForPlan();

        return view('tenant.orders.show', compact('order', 'statuses'));
    }

    /**
     * Show the form for editing an order.
     */
    public function edit(Order $order): View
    {
        $customers = Customer::orderBy('name')->get();
        $services = Service::active()->orderBy('sort_order')->orderBy('name')->get();
        $statuses = Order::statusLabelsForPlan();

        return view('tenant.orders.edit', compact('order', 'customers', 'services', 'statuses'));
    }

    /**
     * Update the specified order.
     */
    public function update(OrderRequest $request, Order $order): RedirectResponse
    {
        $data = $request->validated();
        $data = $this->prepareOrderData($data);

        $order->update($data);

        return redirect()->route('tenant.orders.index')
            ->with('success', "Order {$order->order_number} updated successfully.");
    }

    /**
     * Update the order status (quick action from show/index).
     */
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(Order::statusLabelsForPlan()))],
        ]);

        $oldStatus = $order->status;
        $order->update(['status' => $request->status]);

        // Dispatch event for notifications and loyalty
        OrderStatusChanged::dispatch($order, $oldStatus, $request->status);

        return back()->with('success', "Order {$order->order_number} status updated to {$order->status_label}.");
    }

    /**
     * Mark an order as paid.
     */
    public function markPaid(Order $order): RedirectResponse
    {
        $order->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        return back()->with('success', "Order {$order->order_number} marked as paid.");
    }

    /**
     * Show printable receipt for an order.
     */
    public function receipt(Order $order): View
    {
        $order->load(['customer', 'service']);

        return view('tenant.orders.receipt', compact('order'));
    }

    /**
     * Remove the specified order.
     */
    public function destroy(Order $order): RedirectResponse
    {
        $order->delete();

        return redirect()->route('tenant.orders.index')
            ->with('success', 'Order deleted successfully.');
    }

    /**
     * Normalize order pricing data before persisting it.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareOrderData(array $data): array
    {
        $service = ! empty($data['service_id'])
            ? Service::find((int) $data['service_id'])
            : null;

        if ($service) {
            $data['items'] = $service->prepareOrderItems((array) ($data['items'] ?? []));
            $data['weight'] = $service->requiresWeight()
                ? (float) ($data['weight'] ?? 0)
                : null;
            $data['total_amount'] = $service->calculateOrderTotal(
                $service->requiresWeight() ? (float) ($data['weight'] ?? 0) : null,
                $data['items'],
            );

            return $data;
        }

        $data['items'] = array_map(
            fn (array $item): array => [
                'name' => $item['name'],
                'qty' => $item['qty'],
                'price' => round((float) ($item['price'] ?? 0), 2),
            ],
            Service::normalizeItemEntries((array) ($data['items'] ?? [])),
        );
        $data['weight'] = null;
        $data['total_amount'] = Service::calculateItemizedTotal($data['items']);

        return $data;
    }
}
