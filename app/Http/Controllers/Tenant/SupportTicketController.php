<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\SupportTicketRequest;
use App\Mail\TenantRepliedToTicket;
use App\Models\Admin;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Notifications\AdminGenericNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function index(): View
    {
        $tickets = SupportTicket::query()
            ->where('tenant_id', tenant()->id)
            ->withCount('messages')
            ->latest()
            ->paginate(10);

        return view('tenant.support.index', compact('tickets'));
    }

    public function store(SupportTicketRequest $request): RedirectResponse
    {
        $ticket = SupportTicket::create([
            'tenant_id' => tenant()->id,
            'submitted_by_name' => $request->user()->name,
            'submitted_by_email' => $request->user()->email,
            'subject' => $request->validated('subject'),
            'message' => $request->validated('message'),
            'priority' => $request->validated('priority', 'normal'),
            'category' => $request->validated('category', 'general'),
            'status' => 'open',
        ]);

        // Calculate SLA
        $ticket->calculateSLA();

        // Create initial message
        $message = SupportMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_type' => 'tenant',
            'sender_id' => $request->user()->id,
            'message' => $request->validated('message'),
        ]);

        // Increment unread for admins
        $ticket->incrementUnreadAdmin();

        // Notify admins
        $shopName = tenant()->data['shop_name'] ?? tenant()->id;
        Admin::on('mysql')->get()->each(function (Admin $admin) use ($ticket, $shopName): void {
            $admin->notify(new AdminGenericNotification(
                'New support ticket from '.$shopName.' - '.$ticket->subject
            ));
        });

        return redirect()->route('tenant.support.show', $ticket)
            ->with('success', 'Support ticket created successfully.');
    }

    public function show(SupportTicket $ticket): View
    {
        abort_unless($ticket->tenant_id === tenant()->id, 404);

        $ticket->load(['messages' => fn ($q) => $q->orderBy('created_at')]);
        
        // Mark as read by tenant
        $ticket->markReadByTenant();

        return view('tenant.support.show', compact('ticket'));
    }

    public function sendMessage(Request $request, SupportTicket $ticket): RedirectResponse
    {
        abort_unless($ticket->tenant_id === tenant()->id, 404);

        $request->validate([
            'message' => 'required|string|max:5000',
            'attachments.*' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx',
        ]);

        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('support-attachments/'.tenant()->id, 'public');
                $attachmentPaths[] = $path;
            }
        }

        $message = SupportMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_type' => 'tenant',
            'sender_id' => $request->user()->id,
            'message' => $request->input('message'),
            'attachment_paths' => $attachmentPaths,
        ]);

        // Reopen ticket if it was closed
        if ($ticket->status === 'closed') {
            $ticket->update(['status' => 'open']);
        }

        // Increment unread for admins
        $ticket->incrementUnreadAdmin();

        // Send email to admins
        $shopName = tenant()->data['shop_name'] ?? tenant()->id;
        Admin::on('mysql')->get()->each(function (Admin $admin) use ($ticket, $message, $shopName): void {
            $admin->notify(new AdminGenericNotification(
                'New message on ticket #'.$ticket->id.' from '.$shopName
            ));
            Mail::to($admin->email)->send(new TenantRepliedToTicket($ticket, $message));
        });

        return back()->with('success', 'Message sent successfully.');
    }
}
