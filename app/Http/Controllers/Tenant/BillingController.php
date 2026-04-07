<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class BillingController extends Controller
{
    /**
     * Display the tenant's invoices.
     */
    public function index(): View
    {
        $tenant = tenant();

        $invoices = Invoice::where('tenant_id', $tenant->id)
            ->with('subscriptionPlan')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Get billing summary
        $totalPaid = Invoice::where('tenant_id', $tenant->id)
            ->where('status', 'paid')
            ->sum('total');

        $pendingAmount = Invoice::where('tenant_id', $tenant->id)
            ->whereIn('status', ['issued', 'overdue'])
            ->sum('total');

        return view('tenant.billing.index', [
            'invoices' => $invoices,
            'tenant' => $tenant,
            'totalPaid' => $totalPaid,
            'pendingAmount' => $pendingAmount,
        ]);
    }

    /**
     * Display a specific invoice.
     */
    public function show(Invoice $invoice): View
    {
        $tenant = tenant();

        // Ensure tenant can only view their own invoices
        if ($invoice->tenant_id !== $tenant->id) {
            abort(403, 'You are not authorized to view this invoice.');
        }

        $invoice->load(['subscriptionPlan', 'payment']);

        return view('tenant.billing.show', [
            'invoice' => $invoice,
            'tenant' => $tenant,
        ]);
    }

    /**
     * Download invoice as printable HTML.
     */
    public function download(Invoice $invoice)
    {
        $tenant = tenant();

        // Ensure tenant can only download their own invoices
        if ($invoice->tenant_id !== $tenant->id) {
            abort(403, 'You are not authorized to download this invoice.');
        }

        $invoice->load(['tenant.registration', 'subscriptionPlan']);

        // Generate HTML for the invoice
        $html = view('tenant.billing.pdf', compact('invoice'))->render();

        return Response::make($html, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'attachment; filename="'.$invoice->invoice_number.'.html"',
        ]);
    }
}
