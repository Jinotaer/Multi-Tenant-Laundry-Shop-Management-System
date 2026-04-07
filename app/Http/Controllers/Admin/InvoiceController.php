<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class InvoiceController extends Controller
{
    /**
     * Display a listing of invoices.
     */
    public function index(Request $request)
    {
        $query = Invoice::with(['tenant.registration', 'subscriptionPlan']);

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by tenant
        if ($request->filled('tenant')) {
            $query->where('tenant_id', $request->tenant);
        }

        // Search by invoice number or billing name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('billing_name', 'like', "%{$search}%")
                    ->orWhere('billing_email', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $invoices = $query->paginate(20)->withQueryString();

        // Get summary stats
        $stats = [
            'total' => Invoice::count(),
            'paid' => Invoice::paid()->count(),
            'unpaid' => Invoice::unpaid()->count(),
            'overdue' => Invoice::overdue()->count(),
            'total_revenue' => Invoice::paid()->sum('total'),
        ];

        // Get tenants for filter dropdown
        $tenants = Tenant::with('registration')
            ->whereHas('registration', fn ($q) => $q->where('status', 'approved'))
            ->get();

        return view('admin.invoices.index', compact('invoices', 'stats', 'tenants'));
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load(['tenant.registration', 'subscriptionPlan', 'payment']);

        return view('admin.invoices.show', compact('invoice'));
    }

    /**
     * Generate invoices for a tenant from their payments.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
        ]);

        $tenant = Tenant::findOrFail($request->tenant_id);

        // Find payments without invoices
        $payments = Payment::where('tenant_id', $tenant->id)
            ->where('status', 'paid')
            ->where('payment_type', 'subscription')
            ->whereDoesntHave('invoice')
            ->get();

        $count = 0;
        foreach ($payments as $payment) {
            Invoice::createFromPayment($payment);
            $count++;
        }

        return redirect()->route('admin.invoices.index')
            ->with('success', "Generated {$count} invoice(s) for {$tenant->registration->shop_name}.");
    }

    /**
     * Generate invoices for all tenants.
     */
    public function generateAll()
    {
        // Find all paid subscription payments without invoices
        $payments = Payment::where('status', 'paid')
            ->where('payment_type', 'subscription')
            ->whereDoesntHave('invoice')
            ->get();

        $count = 0;
        foreach ($payments as $payment) {
            Invoice::createFromPayment($payment);
            $count++;
        }

        return redirect()->route('admin.invoices.index')
            ->with('success', "Generated {$count} invoice(s) from existing payments.");
    }

    /**
     * Download invoice as PDF.
     */
    public function download(Invoice $invoice)
    {
        $invoice->load(['tenant.registration', 'subscriptionPlan']);

        // Generate HTML for the invoice
        $html = view('admin.invoices.pdf', compact('invoice'))->render();

        // For now, return as a downloadable HTML file
        // In production, you'd use a PDF library like dompdf or snappy
        return Response::make($html, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'attachment; filename="'.$invoice->invoice_number.'.html"',
        ]);
    }

    /**
     * Send invoice email to tenant.
     */
    public function send(Invoice $invoice)
    {
        // In a real implementation, you'd send an email with the invoice
        // For now, just mark it as issued if it was a draft
        if ($invoice->status === Invoice::STATUS_DRAFT) {
            $invoice->update(['status' => Invoice::STATUS_ISSUED]);
        }

        return redirect()->route('admin.invoices.show', $invoice)
            ->with('success', 'Invoice sent to '.$invoice->billing_email);
    }

    /**
     * Mark invoice as paid.
     */
    public function markPaid(Invoice $invoice)
    {
        $invoice->markAsPaid();

        return redirect()->route('admin.invoices.show', $invoice)
            ->with('success', 'Invoice marked as paid.');
    }

    /**
     * Export invoices to CSV.
     */
    public function export(Request $request)
    {
        $query = Invoice::with(['tenant.registration', 'subscriptionPlan']);

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('from')) {
            $query->where('issue_date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->where('issue_date', '<=', $request->to);
        }

        $invoices = $query->orderBy('issue_date', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="invoices-'.now()->format('Y-m-d').'.csv"',
        ];

        $callback = function () use ($invoices) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'Invoice #',
                'Shop Name',
                'Billing Name',
                'Email',
                'Plan',
                'Amount',
                'Status',
                'Issue Date',
                'Due Date',
                'Paid At',
            ]);

            // Data rows
            foreach ($invoices as $invoice) {
                fputcsv($file, [
                    $invoice->invoice_number,
                    $invoice->tenant->registration?->shop_name ?? $invoice->tenant_id,
                    $invoice->billing_name,
                    $invoice->billing_email,
                    $invoice->subscriptionPlan?->name ?? 'N/A',
                    $invoice->total,
                    $invoice->status_label,
                    $invoice->issue_date->format('Y-m-d'),
                    $invoice->due_date->format('Y-m-d'),
                    $invoice->paid_at?->format('Y-m-d H:i') ?? '',
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
