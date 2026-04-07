<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();

            // Billing details
            $table->string('billing_name');
            $table->string('billing_email');
            $table->string('billing_address')->nullable();

            // Amounts
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('currency', 3)->default('PHP');

            // Dates
            $table->date('issue_date');
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();

            // Status: draft, issued, paid, overdue, cancelled
            $table->string('status')->default('draft');

            // Period covered
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();

            // Notes
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index('issue_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
