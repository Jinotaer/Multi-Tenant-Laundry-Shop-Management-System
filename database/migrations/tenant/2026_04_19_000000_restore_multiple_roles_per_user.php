<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop foreign keys first
        Schema::table('role_user', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropForeign(['user_id']);
        });

        // Drop the unique constraint on user_id (allows multiple roles per user)
        Schema::table('role_user', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
        });

        // Add back the composite unique constraint (role_id + user_id)
        // This prevents duplicate role assignments but allows multiple roles
        Schema::table('role_user', function (Blueprint $table) {
            $table->unique(['role_id', 'user_id']);
        });

        // Recreate foreign keys
        Schema::table('role_user', function (Blueprint $table) {
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        // Drop foreign keys
        Schema::table('role_user', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropForeign(['user_id']);
        });

        // Drop composite unique constraint
        Schema::table('role_user', function (Blueprint $table) {
            $table->dropUnique(['role_id', 'user_id']);
        });

        // Add back single role constraint
        Schema::table('role_user', function (Blueprint $table) {
            $table->unique('user_id');
        });

        // Recreate foreign keys
        Schema::table('role_user', function (Blueprint $table) {
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
