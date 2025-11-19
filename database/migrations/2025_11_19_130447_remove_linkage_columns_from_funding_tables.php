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
        // Remove linkage column from tabungans table
        Schema::table('tabungans', function (Blueprint $table) {
            $table->dropColumn('linkage');
        });

        // Remove linkage column from depositos table
        Schema::table('depositos', function (Blueprint $table) {
            $table->dropColumn('linkage');
        });

        // Remove linkage column from pembiayaans table
        Schema::table('pembiayaans', function (Blueprint $table) {
            $table->dropColumn('linkage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add linkage column back to tabungans table
        Schema::table('tabungans', function (Blueprint $table) {
            $table->decimal('linkage', 20, 2)->default(0)->after('avgeom');
        });

        // Add linkage column back to depositos table
        Schema::table('depositos', function (Blueprint $table) {
            $table->decimal('linkage', 20, 2)->default(0)->after('nisbahrp');
        });

        // Add linkage column back to pembiayaans table
        Schema::table('pembiayaans', function (Blueprint $table) {
            $table->decimal('linkage', 20, 2)->default(0)->after('osmdlc');
        });
    }
};
