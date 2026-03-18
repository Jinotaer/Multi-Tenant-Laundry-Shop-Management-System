<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ServiceRequest;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ServiceController extends Controller
{
    /**
     * Display a listing of all services.
     */
    public function index(): View
    {
        $services = Service::orderBy('sort_order')->orderBy('name')->get();
        $pricingMode = Service::pricingMode();
        $priceTypeDescriptions = Service::priceTypeDescriptions();

        return view('tenant.services.index', compact('services', 'pricingMode', 'priceTypeDescriptions'));
    }

    /**
     * Show the form for creating a new service.
     */
    public function create(): View
    {
        $priceTypes = Service::availablePriceTypes();
        $pricingMode = Service::pricingMode();
        $priceTypeDescriptions = Service::priceTypeDescriptions();

        return view('tenant.services.create', compact('priceTypes', 'pricingMode', 'priceTypeDescriptions'));
    }

    /**
     * Store a newly created service.
     */
    public function store(ServiceRequest $request): RedirectResponse
    {
        Service::create($request->validated());

        return redirect()->route('tenant.services.index')
            ->with('success', 'Service created successfully.');
    }

    /**
     * Show the form for editing a service.
     */
    public function edit(Service $service): View
    {
        $priceTypes = Service::availablePriceTypes();
        $pricingMode = Service::pricingMode();
        $priceTypeDescriptions = Service::priceTypeDescriptions();

        return view('tenant.services.edit', compact('service', 'priceTypes', 'pricingMode', 'priceTypeDescriptions'));
    }

    /**
     * Update the specified service.
     */
    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $service->update($request->validated());

        return redirect()->route('tenant.services.index')
            ->with('success', 'Service updated successfully.');
    }

    /**
     * Remove the specified service.
     */
    public function destroy(Service $service): RedirectResponse
    {
        $service->delete();

        return redirect()->route('tenant.services.index')
            ->with('success', 'Service deleted successfully.');
    }
}
