<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    /**
     * Display all support tickets.
     */
    public function index(Request $request): View
    {
        $tickets = SupportTicket::query()
            ->with('tenant')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.support-tickets.index', compact('tickets'));
    }

    /**
     * Display a single support ticket.
     */
    public function show(SupportTicket $ticket): View
    {
        $ticket->load('tenant');

        return view('admin.support-tickets.show', compact('ticket'));
    }

    /**
     * Update the status or notes for a support ticket.
     */
    public function update(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $ticket->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? null,
            'resolved_at' => $validated['status'] === 'resolved' ? now() : null,
        ]);

        return redirect()->route('admin.support-tickets.show', $ticket)
            ->with('success', 'Support ticket updated successfully.');
    }
}
