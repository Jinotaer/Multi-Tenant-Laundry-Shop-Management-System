<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->timestamp('subscription_expires_at')->nullable()->after('trial_ends_at');
            $table->integer('grace_period_days')->default(7)->after('subscription_expires_at');
            $table->timestamp('last_renewal_reminder_sent_at')->nullable()->after('grace_period_days');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['subscription_expires_at', 'grace_period_days', 'last_renewal_reminder_sent_at']);
        });
    }
};
