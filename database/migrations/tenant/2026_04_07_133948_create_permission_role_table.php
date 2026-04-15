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
        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['permission_id', 'role_id']);
        });

        $ownerRoleId = DB::table('roles')
            ->where('slug', 'owner')
            ->value('id');

        $permissionIds = DB::table('permissions')
            ->pluck('id')
            ->all();

        if ($ownerRoleId === null || $permissionIds === []) {
            return;
        }

        $timestamp = now();
        $assignments = array_map(
            fn (int $permissionId): array => [
                'permission_id' => $permissionId,
                'role_id' => $ownerRoleId,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            $permissionIds,
        );

        DB::table('permission_role')->insertOrIgnore($assignments);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_role');
    }
};
