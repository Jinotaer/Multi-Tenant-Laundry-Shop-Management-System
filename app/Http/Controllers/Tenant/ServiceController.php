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
        $priceTypes = Service::availablePriceTypes();
        $priceTypeDescriptions = Service::priceTypeDescriptions();

        // Determine if we need to show the edit modal
        $editService = null;
        if (request()->has('edit')) {
            $editService = Service::find(request('edit'));
        } elseif ($errors = session('errors')) {
            // Re-open edit modal on validation failure
            if (old('form_context') === 'service-edit' && old('service_id')) {
                $editService = Service::find(old('service_id'));
            }
        }

        return view('tenant.services.index', compact(
            'services',
            'pricingMode',
            'priceTypes',
            'priceTypeDescriptions',
            'editService'
        ));
    }

    /**
     * Redirect create requests to the index page and open the create modal.
     */
    public function create(): RedirectResponse
    {
        return redirect()->route('tenant.services.index', ['create' => 1]);
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
     * Redirect edit requests to the index page and open the edit modal.
     */
    public function edit(Service $service): RedirectResponse
    {
        return redirect()->route('tenant.services.index', ['edit' => $service->getKey()]);
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
