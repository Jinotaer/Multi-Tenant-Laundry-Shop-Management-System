<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add fields to support_tickets table
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->string('category')->nullable()->after('priority');
            $table->integer('assigned_to')->nullable()->after('status');
            $table->timestamp('first_response_at')->nullable()->after('resolved_at');
            $table->timestamp('sla_due_at')->nullable()->after('first_response_at');
            $table->boolean('sla_breached')->default(false)->after('sla_due_at');
            $table->integer('unread_tenant_count')->default(0)->after('sla_breached');
            $table->integer('unread_admin_count')->default(0)->after('unread_tenant_count');
        });

        // Update support_messages table for file attachments
        Schema::table('support_messages', function (Blueprint $table) {
            $table->json('attachment_paths')->nullable()->after('message');
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn([
                'category',
                'assigned_to',
                'first_response_at',
                'sla_due_at',
                'sla_breached',
                'unread_tenant_count',
                'unread_admin_count',
            ]);
        });

        Schema::table('support_messages', function (Blueprint $table) {
            $table->dropColumn('attachment_paths');
        });
    }
};
