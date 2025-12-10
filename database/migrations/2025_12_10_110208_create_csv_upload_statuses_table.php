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
        Schema::create('csv_upload_statuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('month');
            $table->string('year');
            $table->string('status')->default('processing'); // processing, completed, failed
            $table->integer('total_records')->default(0);
            $table->integer('processed_records')->default(0);
            $table->integer('error_count')->default(0);
            $table->text('message')->nullable();
            $table->json('errors')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['month', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('csv_upload_statuses');
    }
};
