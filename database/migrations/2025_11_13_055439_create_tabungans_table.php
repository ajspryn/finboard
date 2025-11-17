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
        Schema::create('tabungans', function (Blueprint $table) {
            $table->id();

            // Primary fields from CSV
            $table->string('notab')->index(); // Nomor Tabungan (Primary Key di Core Banking)
            $table->string('nocif')->nullable()->index(); // Nomor CIF
            $table->string('kodeprd')->nullable(); // Kode Produk
            $table->string('fnama')->nullable(); // Full Nama Nasabah
            $table->string('namaqq')->nullable(); // Nama QQ

            // Financial fields
            $table->decimal('sahirrp', 20, 2)->default(0); // Saldo Akhir Rupiah
            $table->decimal('saldoblok', 20, 2)->default(0); // Saldo Blokir
            $table->decimal('tax', 20, 2)->default(0)->nullable(); // Pajak
            $table->decimal('avgeom', 20, 2)->default(0)->nullable(); // Average End of Month

            // Status fields
            $table->string('stsrec')->nullable(); // Status Record (A=Aktif, dll)
            $table->string('stsrest')->nullable(); // Status Restrict
            $table->string('stspep')->nullable(); // Status PEP
            $table->string('kdrisk')->nullable(); // Kode Risk

            // Date fields
            $table->date('tgltrnakh')->nullable(); // Tanggal Transaksi Akhir
            $table->date('tgllhr')->nullable(); // Tanggal Lahir

            // Contact & Identity
            $table->string('noid')->nullable(); // Nomor Identitas
            $table->string('hp')->nullable(); // Handphone
            $table->string('nmibu')->nullable(); // Nama Ibu Kandung

            // Other fields
            $table->string('ketsandi')->nullable(); // Keterangan Sandi
            $table->string('namapt')->nullable(); // Nama PT (untuk corporate)
            $table->string('kodeloc')->nullable(); // Kode Lokasi/Cabang

            // Period tracking
            $table->string('period_month', 2); // 01-12
            $table->integer('period_year'); // 2024, 2025, dst

            $table->timestamps();

            // Indexes untuk performa
            $table->index(['notab', 'period_year', 'period_month']);
            $table->index(['period_year', 'period_month']);
            $table->index(['stsrec']); // untuk filter status
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tabungans');
    }
};
