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
        // Use raw SQL to check and drop index safely
        $connection = Schema::getConnection();
        $dbName = $connection->getDatabaseName();
        $driver = $connection->getDriverName();

        if ($driver === 'mysql') {
            // For MySQL, check if index exists and drop it
            $indexes = $connection->select("
                SELECT INDEX_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?
            ", [$dbName, 'tabungans', 'tabungans_ao_idx']);

            if (count($indexes) > 0) {
                Schema::table('tabungans', function (Blueprint $table) {
                    $table->dropIndex('tabungans_ao_idx');
                });
            }
        } elseif ($driver === 'sqlite') {
            // For SQLite, just try to drop (it will be safe if it doesn't exist)
            try {
                $connection->statement('DROP INDEX IF EXISTS tabungans_ao_idx');
            } catch (\Exception $e) {
                // Ignore if index doesn't exist
            }
        }
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
