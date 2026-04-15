<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'user_id']);
        });

        $roleIds = DB::table('roles')
            ->pluck('id', 'slug');

        $timestamp = now();
        $assignments = DB::table('users')
            ->select('id', 'role')
            ->get()
            ->filter(fn (object $user): bool => isset($roleIds[$user->role]))
            ->map(fn (object $user): array => [
                'role_id' => $roleIds[$user->role],
                'user_id' => $user->id,
                'assigned_by' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])
            ->values()
            ->all();

        if ($assignments !== []) {
            DB::table('role_user')->insertOrIgnore($assignments);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_user');
    }
};
