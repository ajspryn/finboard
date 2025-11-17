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
        Schema::create('depositos', function (Blueprint $table) {
            $table->id();

            // Primary fields from CSV
            $table->string('nodep')->index(); // Nomor Deposito (Primary Key di Core Banking)
            $table->string('nocif')->nullable()->index(); // Nomor CIF
            $table->string('nobilyet')->nullable(); // Nomor Bilyet
            $table->string('nama')->nullable(); // Nama Nasabah

            // Financial fields
            $table->decimal('nomrp', 20, 2)->default(0); // Nominal Rupiah (Saldo Deposito)
            $table->decimal('tax', 20, 2)->default(0)->nullable(); // Pajak
            $table->decimal('bnghtg', 20, 2)->default(0)->nullable(); // Bunga Hitung
            $table->decimal('nisbahrp', 20, 2)->default(0)->nullable(); // Nisbah Rupiah

            // Rate & Nisbah fields
            $table->decimal('nisbah', 8, 2)->default(0)->nullable(); // Nisbah %
            $table->decimal('spread', 8, 2)->default(0)->nullable(); // Spread
            $table->decimal('equivrate', 8, 3)->default(0)->nullable(); // Equivalent Rate
            $table->decimal('komitrate', 8, 3)->default(0)->nullable(); // Komitmen Rate

            // Product & Time Period
            $table->string('kdprd')->nullable(); // Kode Produk
            $table->string('jkwaktu')->nullable(); // Jangka Waktu (angka)
            $table->string('jnsjkwaktu')->nullable(); // Jenis Jangka Waktu (B=Bulan, dll)
            $table->string('aro')->nullable(); // ARO (Auto Roll Over): Y/N

            // Status fields
            $table->string('stsrec')->nullable(); // Status Record (A=Aktif, dll)
            $table->string('ststrn')->nullable(); // Status Transaksi
            $table->string('stspep')->nullable(); // Status PEP
            $table->string('kdrisk')->nullable(); // Kode Risk
            $table->string('stskait')->nullable(); // Status Kait
            $table->string('golcustbi')->nullable(); // Golongan Customer BI

            // Date fields
            $table->date('tglbuka')->nullable(); // Tanggal Buka
            $table->date('tgleff')->nullable(); // Tanggal Efektif
            $table->date('tgljtempo')->nullable(); // Tanggal Jatuh Tempo
            $table->date('tgllhr')->nullable(); // Tanggal Lahir

            // Location fields
            $table->string('kdwil')->nullable(); // Kode Wilayah
            $table->string('kodeaoh')->nullable(); // Kode AOH
            $table->string('kodeaop')->nullable(); // Kode AOP
            $table->string('alamat')->nullable(); // Alamat
            $table->string('kota')->nullable(); // Kota
            $table->string('kelurahan')->nullable(); // Kelurahan
            $table->string('kecamatan')->nullable(); // Kecamatan
            $table->string('kdpos')->nullable(); // Kode Pos

            // Contact & Identity
            $table->string('noid')->nullable(); // Nomor Identitas
            $table->string('telprmh')->nullable(); // Telepon Rumah
            $table->string('hp')->nullable(); // Handphone
            $table->string('nmibu')->nullable(); // Nama Ibu Kandung

            // Account fields
            $table->string('noacbng')->nullable(); // Nomor AC Bunga
            $table->string('tambahnom')->nullable(); // Tambah Nominal (Y/N)

            // Other fields
            $table->string('ketsandi')->nullable(); // Keterangan Sandi
            $table->string('namapt')->nullable(); // Nama PT (untuk corporate)

            // Period tracking
            $table->string('period_month', 2); // 01-12
            $table->integer('period_year'); // 2024, 2025, dst

            $table->timestamps();

            // Indexes untuk performa
            $table->index(['nodep', 'period_year', 'period_month']);
            $table->index(['period_year', 'period_month']);
            $table->index(['stsrec']); // untuk filter status
            $table->index(['tgljtempo']); // untuk cek jatuh tempo
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('depositos');
    }
};
