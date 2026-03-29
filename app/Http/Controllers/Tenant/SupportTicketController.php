<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\SupportTicketRequest;
use App\Models\Admin;
use App\Models\SupportTicket;
use App\Notifications\AdminGenericNotification;
use Illuminate\Http\RedirectResponse;
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
            'priority' => 'priority',
            'status' => 'open',
        ]);

        Admin::query()->get()->each(function (Admin $admin) use ($ticket): void {
            $admin->notify(new AdminGenericNotification(
                'New priority support ticket from '.tenant('data')['shop_name'].' - '.$ticket->subject
            ));
        });

        return redirect()->route('tenant.support.index')
            ->with('success', 'Priority support ticket submitted successfully.');
    }
}
