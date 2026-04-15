<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\SupportTicketRequest;
use App\Models\Admin;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Notifications\AdminGenericNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    /**
     * Display the tenant's support ticket inbox.
     */
    public function index(): View
    {
        $tickets = SupportTicket::query()
            ->where('tenant_id', tenant()->id)
            ->withCount('messages')
            ->latest()
            ->paginate(10);

        return view('tenant.support.index', compact('tickets'));
    }

    /**
     * Submit a new priority support ticket.
     */
    public function store(SupportTicketRequest $request): RedirectResponse
    {
        $ticket = SupportTicket::create([
            'tenant_id' => tenant()->id,
            'submitted_by_name' => $request->user()->name,
            'submitted_by_email' => $request->user()->email,
            'subject' => $request->validated('subject'),
            'message' => $request->validated('message'),
            'priority' => $request->validated('priority', 'normal'),
            'status' => 'open',
        ]);

        // Create initial message
        SupportMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_type' => 'tenant',
            'sender_id' => $request->user()->id,
            'message' => $request->validated('message'),
        ]);

        // Notify admins on central database
        Admin::on('mysql')->get()->each(function (Admin $admin) use ($ticket): void {
            $admin->notify(new AdminGenericNotification(
                'New support ticket from '.tenant('data')['shop_name'].' - '.$ticket->subject
            ));
        });

        return redirect()->route('tenant.support.show', $ticket)
            ->with('success', 'Support ticket created successfully.');
    }

    /**
     * Show a specific ticket with chat messages.
     */
    public function show(SupportTicket $ticket): View
    {
        abort_unless($ticket->tenant_id === tenant()->id, 404);

        $ticket->load(['messages' => fn ($q) => $q->orderBy('created_at')]);

        return view('tenant.support.show', compact('ticket'));
    }

    /**
     * Send a message in a ticket.
     */
    public function sendMessage(Request $request, SupportTicket $ticket): RedirectResponse
    {
        abort_unless($ticket->tenant_id === tenant()->id, 404);

        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        SupportMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_type' => 'tenant',
            'sender_id' => $request->user()->id,
            'message' => $request->input('message'),
        ]);

        // Reopen ticket if it was closed
        if ($ticket->status === 'closed') {
            $ticket->update(['status' => 'open']);
        }

        Admin::on('mysql')->get()->each(function (Admin $admin) use ($ticket): void {
            $admin->notify(new AdminGenericNotification(
                'New message on ticket #'.$ticket->id.' from '.tenant('data')['shop_name']
            ));
        });

        return back()->with('success', 'Message sent successfully.');
    }
}
