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
        Schema::table('tabungans', function (Blueprint $table) {
            // Drop the incorrect tabungans_ao_idx that references non-existent nmao column
            // This index was incorrectly created by a previous migration
            try {
                $table->dropIndex('tabungans_ao_idx');
            } catch (\Exception $e) {
                // Index might not exist or already dropped, continue
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tabungans', function (Blueprint $table) {
            // We don't recreate the incorrect index in rollback
        });
    }
};
