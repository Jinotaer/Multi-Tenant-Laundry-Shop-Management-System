<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CustomerRequest;
use App\Models\Customer;
use App\Services\PlanLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class CustomerController extends Controller
{
    /**
     * Display a listing of all customers.
     */
    public function index(Request $request): View
    {
        $customers = Customer::query()
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('phone', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"))
            ->withCount('orders')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $planLimitService = new PlanLimitService(tenant());
        $user = $request->user();
        $canCreateCustomer = $user !== null
            && ($user->isOwner() || $user->hasPermission('customers.create'));
        $canUpdateCustomer = $user !== null
            && ($user->isOwner() || $user->hasPermission('customers.update'));
        $canDeleteCustomer = $user !== null
            && ($user->isOwner() || $user->hasPermission('customers.delete'));
        $canAddCustomer = $planLimitService->canAddCustomer(Customer::count());

        return view('tenant.customers.index', compact(
            'customers',
            'canCreateCustomer',
            'canUpdateCustomer',
            'canDeleteCustomer',
            'canAddCustomer',
        ));
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create(): RedirectResponse
    {
        $planLimitService = new PlanLimitService(tenant());

        if (! $planLimitService->canAddCustomer(Customer::count())) {
            return redirect()->route('tenant.customers.index')
                ->with('error', 'Customer limit reached for your current plan. Please upgrade to add more customers.');
        }

        return redirect()->route('tenant.customers.index', ['create' => 1]);
    }

    /**
     * Store a newly created customer.
     */
    public function store(CustomerRequest $request): RedirectResponse
    {
        $planLimitService = new PlanLimitService(tenant());

        if (! $planLimitService->canAddCustomer(Customer::count())) {
            return redirect()->route('tenant.customers.index')
                ->with('error', 'Customer limit reached for your current plan. Please upgrade to add more customers.');
        }

        $customer = Customer::create([
            ...$request->validated(),
            'password' => null,
            'role' => 'customer',
        ]);

        $successMessage = 'Customer added successfully.';

        if (filled($customer->email)) {
            $customer->sendPasswordResetNotification(
                Password::broker('customers')->createToken($customer),
            );

            $successMessage = 'Customer added successfully. A password setup email was sent to the customer.';
        }

        return redirect()->route('tenant.customers.index')
            ->with('success', $successMessage);
    }

    /**
     * Display the specified customer.
     */
    public function show(Customer $customer): View
    {
        if (tenant()->hasFeature('customer_loyalty')) {
            $customer->load('loyalty');
        }

        $orders = $customer->orders()->with('customer')->latest()->paginate(10);

        return view('tenant.customers.show', compact('customer', 'orders'));
    }

    /**
     * Show the form for editing a customer.
     */
    public function edit(Customer $customer): View
    {
        return view('tenant.customers.edit', compact('customer'));
    }

    /**
     * Update the specified customer.
     */
    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()->route('tenant.customers.index')
            ->with('success', 'Customer updated successfully.');
    }

    /**
     * Remove the specified customer.
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()->route('tenant.customers.index')
            ->with('success', 'Customer deleted successfully.');
    }
}
