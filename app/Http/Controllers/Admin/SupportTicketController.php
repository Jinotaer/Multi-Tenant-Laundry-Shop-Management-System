<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminRepliedToTicket;
use App\Models\Admin;
use App\Models\CannedResponse;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
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

    public function show(SupportTicket $ticket): View
    {
        $ticket->load(['tenant', 'messages' => fn ($q) => $q->orderBy('created_at')]);
        
        // Mark as read by admin
        $ticket->markReadByAdmin();
        
        // Get canned responses
        $cannedResponses = CannedResponse::active()->orderBy('title')->get();
        
        // Get available admins for assignment
        $admins = Admin::on('mysql')->orderBy('name')->get();

        return view('admin.support-tickets.show', compact('ticket', 'cannedResponses', 'admins'));
    }

    public function update(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
            'assigned_to' => ['nullable', 'exists:admins,id'],
            'category' => ['nullable', 'string'],
        ]);

        $ticket->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? null,
            'assigned_to' => $validated['assigned_to'] ?? null,
            'category' => $validated['category'] ?? null,
            'resolved_at' => in_array($validated['status'], ['resolved', 'closed']) ? now() : null,
        ]);

        return redirect()->route('admin.support-tickets.show', $ticket)
            ->with('success', 'Support ticket updated successfully.');
    }

    public function sendMessage(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $request->validate([
            'message' => 'required|string|max:5000',
            'attachments.*' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx',
            'canned_response_id' => 'nullable|exists:canned_responses,id',
        ]);

        $admin = $request->user('admin');
        
        // Use canned response if selected
        $message = $request->input('message');
        if ($request->filled('canned_response_id')) {
            $cannedResponse = CannedResponse::find($request->input('canned_response_id'));
            $message = $cannedResponse->content;
            $cannedResponse->incrementUsage();
        }

        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('support-attachments/admin', 'public');
                $attachmentPaths[] = $path;
            }
        }

        $supportMessage = SupportMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_type' => 'admin',
            'sender_id' => $admin->id,
            'message' => $message,
            'attachment_paths' => $attachmentPaths,
        ]);

        // Mark first response
        $ticket->markFirstResponse();

        // Update ticket status to in_progress if it was open
        if ($ticket->status === 'open') {
            $ticket->update(['status' => 'in_progress']);
        }
        
        // Increment unread for tenant
        $ticket->incrementUnreadTenant();

        // Send email to tenant
        Mail::to($ticket->submitted_by_email)->send(new AdminRepliedToTicket($ticket, $supportMessage));

        return back()->with('success', 'Message sent successfully.');
    }
}
