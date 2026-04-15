<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
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
            ->withCount('messages')
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
        $ticket->load(['tenant', 'messages' => fn ($q) => $q->orderBy('created_at')]);

        return view('admin.support-tickets.show', compact('ticket'));
    }

    /**
     * Update the status or notes for a support ticket.
     */
    public function update(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $ticket->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? null,
            'resolved_at' => in_array($validated['status'], ['resolved', 'closed']) ? now() : null,
        ]);

        return redirect()->route('admin.support-tickets.show', $ticket)
            ->with('success', 'Support ticket updated successfully.');
    }

    /**
     * Send a message in a ticket.
     */
    public function sendMessage(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $admin = $request->user('admin');

        SupportMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_type' => 'admin',
            'sender_id' => $admin->id,
            'message' => $request->input('message'),
        ]);

        // Update ticket status to in_progress if it was open
        if ($ticket->status === 'open') {
            $ticket->update(['status' => 'in_progress']);
        }

        return back()->with('success', 'Message sent successfully.');
    }
}
