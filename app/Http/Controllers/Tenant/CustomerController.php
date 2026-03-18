<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CustomerRequest;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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

        return view('tenant.customers.index', compact('customers'));
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create(): View
    {
        $defaultPassword = Str::random(8);

        return view('tenant.customers.create', compact('defaultPassword'));
    }

    /**
     * Store a newly created customer.
     */
    public function store(CustomerRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Hash password if provided
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $customer = Customer::create($data);

        $generated = null;

        if (! empty($data['email'])) {
            $email = $data['email'];

            $existing = User::where('email', $email)->first();

            if (! $existing) {
                $passwordToUse = $request->input('password') ?? Str::random(8);
                $generated = $request->input('password') ?? $passwordToUse;

                User::create([
                    'name' => $data['name'] ?? $customer->name,
                    'email' => $email,
                    'password' => Hash::make($passwordToUse),
                    'role' => 'customer',
                ]);
            }
        }

        $redirect = redirect()->route('tenant.customers.index')
            ->with('success', 'Customer added successfully.');

        if ($generated) {
            $redirect->with('generated_password', ['email' => $customer->email, 'password' => $generated]);
        }

        return $redirect;
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
