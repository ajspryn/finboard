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
        Schema::create('linkages', function (Blueprint $table) {
            $table->id();
            $table->string('nokontrak')->unique();
            $table->string('nocif')->nullable();
            $table->string('nama');
            $table->date('tgleff')->nullable();
            $table->date('tgljt')->nullable();
            $table->string('kelompok')->nullable();
            $table->string('jnsakad')->nullable();
            $table->decimal('prsnisbah', 5, 2)->default(0);
            $table->decimal('plafon', 20, 2)->default(0);
            $table->decimal('os', 20, 2)->default(0);
            $table->string('sumber_dana')->nullable(); // Dana Pihak 1, 2, 3
            $table->integer('period_month')->nullable();
            $table->integer('period_year')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('linkages');
    }
};
