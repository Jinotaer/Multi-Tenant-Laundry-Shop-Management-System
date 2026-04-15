# Simple Customer Support System

## Overview
A simple chat-like customer support system that allows tenants (shop owners) to communicate with the central admin team through support tickets.

## Features

### For Tenants (Shop Owners)
- ✅ Create support tickets with subject, priority, and message
- ✅ View all their support tickets (open, in progress, closed)
- ✅ Chat-like interface to send messages within a ticket
- ✅ Real-time status updates (open, in progress, closed)
- ✅ Priority levels (normal, high priority)
- ✅ Accessible only for owners with `priority_support` feature

### For Central Admin
- ✅ View all support tickets from all tenants
- ✅ Filter tickets by status (all, open, in progress, closed)
- ✅ Chat-like interface to respond to tenant messages
- ✅ Update ticket status (open, in progress, resolved, closed)
- ✅ Add internal admin notes
- ✅ View tenant information and ticket history

## Database Structure

### Tables Created
1. **support_tickets** (already existed in central database)
   - id
   - tenant_id
   - submitted_by_name
   - submitted_by_email
   - subject
   - message
   - priority (normal, priority)
   - status (open, in_progress, resolved, closed)
   - admin_notes
   - resolved_at
   - timestamps

2. **support_messages** (newly created in central database)
   - id
   - support_ticket_id
   - sender_type (tenant, admin)
   - sender_id
   - message
   - attachments (json, for future use)
   - read_at
   - timestamps

## Models

### SupportTicket
- Location: `app/Models/SupportTicket.php`
- Connection: Central database (mysql)
- Relationships:
  - belongsTo: Tenant
  - hasMany: SupportMessage

### SupportMessage
- Location: `app/Models/SupportMessage.php`
- Connection: Central database (mysql)
- Relationships:
  - belongsTo: SupportTicket
- Methods:
  - isTenantMessage()
  - isAdminMessage()

## Controllers

### Tenant Side
**SupportTicketController** (`app/Http/Controllers/Tenant/SupportTicketController.php`)
- `index()` - List all tickets for the tenant
- `store()` - Create a new support ticket
- `show($ticket)` - View ticket details with chat messages
- `sendMessage($ticket)` - Send a message in the ticket

### Admin Side
**SupportTicketController** (`app/Http/Controllers/Admin/SupportTicketController.php`)
- `index()` - List all tickets from all tenants
- `show($ticket)` - View ticket details with chat messages
- `update($ticket)` - Update ticket status and admin notes
- `sendMessage($ticket)` - Send a message in the ticket

## Routes

### Tenant Routes
```php
Route::middleware(['role:owner', 'feature:priority_support'])->group(function () {
    Route::get('/support', [SupportTicketController::class, 'index'])
        ->name('tenant.support.index');
    Route::post('/support', [SupportTicketController::class, 'store'])
        ->name('tenant.support.store');
    Route::get('/support/{ticket}', [SupportTicketController::class, 'show'])
        ->name('tenant.support.show');
    Route::post('/support/{ticket}/message', [SupportTicketController::class, 'sendMessage'])
        ->name('tenant.support.message');
});
```

### Admin Routes
```php
Route::get('/support-tickets', [AdminSupportTicketController::class, 'index'])
    ->name('admin.support-tickets.index');
Route::get('/support-tickets/{ticket}', [AdminSupportTicketController::class, 'show'])
    ->name('admin.support-tickets.show');
Route::patch('/support-tickets/{ticket}', [AdminSupportTicketController::class, 'update'])
    ->name('admin.support-tickets.update');
Route::post('/support-tickets/{ticket}/message', [AdminSupportTicketController::class, 'sendMessage'])
    ->name('admin.support-tickets.message');
```

## Views

### Tenant Views
1. **index.blade.php** - List of all support tickets with create modal
   - Location: `resources/views/tenant/support/index.blade.php`
   - Features: Modal for creating tickets, ticket list with status badges

2. **show.blade.php** - Chat interface for a specific ticket
   - Location: `resources/views/tenant/support/show.blade.php`
   - Features: Chat messages, send message form, status display

### Admin Views
1. **index.blade.php** - List of all support tickets with filters
   - Location: `resources/views/admin/support-tickets/index.blade.php`
   - Features: Status filters, tenant information, message count

2. **show.blade.php** - Chat interface with ticket management
   - Location: `resources/views/admin/support-tickets/show.blade.php`
   - Features: Chat messages, send message form, status update form, admin notes

## Navigation

### Tenant Sidebar
- Support link added for owners with `priority_support` feature
- Icon: Chat bubble
- Location: After Settings, before Update Center

### Admin Sidebar
- Support Tickets link already exists in admin navigation

## Access Control

| Feature | Owner | Manager | Staff | Customer |
|---------|-------|---------|-------|----------|
| View Support | ✅ (with feature) | ❌ | ❌ | ❌ |
| Create Ticket | ✅ (with feature) | ❌ | ❌ | ❌ |
| Send Messages | ✅ (with feature) | ❌ | ❌ | ❌ |

**Required Feature:** `priority_support` (Premium plan feature)

## Workflow

### Creating a Ticket
1. Owner clicks "New Ticket" button
2. Modal opens with form (subject, priority, message)
3. Ticket is created with status "open"
4. Initial message is added to the ticket
5. Admin receives notification
6. Owner is redirected to ticket chat view

### Chatting
1. Tenant sends message → Status remains or changes to "open"
2. Admin responds → Status changes to "in_progress"
3. Messages appear in chronological order
4. Each message shows sender name and timestamp

### Closing a Ticket
1. Admin updates status to "resolved" or "closed"
2. Ticket is marked with resolved_at timestamp
3. Chat input is disabled for closed tickets
4. Tenant can create a new ticket if needed

## Status Flow
```
open → in_progress → resolved/closed
  ↑         ↓
  └─────────┘ (can reopen if tenant sends new message)
```

## Notifications
- Admin receives notification when:
  - New ticket is created
  - Tenant sends a new message

## Future Enhancements (Not Implemented)
- File attachments
- Email notifications to tenant
- Real-time chat with Pusher/Laravel Echo
- Ticket assignment to specific admins
- Satisfaction ratings
- Canned responses
- Ticket categories
- Search functionality

## Migration
Run the migration to create the support_messages table:
```bash
php artisan migrate
```

## Testing
1. Ensure tenant has `priority_support` feature enabled
2. Login as owner
3. Navigate to Support from sidebar
4. Create a new ticket
5. Send messages
6. Login as admin
7. View and respond to tickets

## Notes
- All support data is stored in the central database
- Tickets are scoped to tenants automatically
- Only owners can access support (feature-gated)
- Chat interface is simple and clean
- No real-time updates (requires page refresh)
