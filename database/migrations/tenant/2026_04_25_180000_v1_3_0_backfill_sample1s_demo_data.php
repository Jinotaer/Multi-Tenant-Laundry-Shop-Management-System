<?php

use Database\Seeders\Sample1Seeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure the Sample1 demo table and its 50 seeded rows exist when tenants
     * update to v1.3.0, even if earlier tagged demo migrations were skipped.
     */
    public function up(): void
    {
        if (! Schema::hasTable('sample1s')) {
            Schema::create('sample1s', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('age');
                $table->timestamps();
            });
        }

        (new Sample1Seeder())->run();
    }

    /**
     * Keep rollback non-destructive because tenants may have edited the demo
     * records after the upgrade.
     */
    public function down(): void
    {
        //
    }
};
