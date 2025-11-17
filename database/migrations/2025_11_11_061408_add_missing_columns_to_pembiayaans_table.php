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
        Schema::table('pembiayaans', function (Blueprint $table) {
            $table->integer('angske_x')->nullable()->after('angs_ke');
            $table->string('kdmco', 50)->nullable()->after('angske_x');
            $table->string('kdsektor', 50)->nullable()->after('kdmco');
            $table->string('kdsub', 50)->nullable()->after('kdsektor');
            $table->decimal('plafon', 20, 2)->nullable()->after('kdsub');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pembiayaans', function (Blueprint $table) {
            $table->dropColumn(['angske_x', 'kdmco', 'kdsektor', 'kdsub', 'plafon']);
        });
    }
};
