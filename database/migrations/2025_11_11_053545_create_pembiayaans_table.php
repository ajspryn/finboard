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
        Schema::create('pembiayaans', function (Blueprint $table) {
            $table->id();
            $table->string('nokontrak')->unique();
            $table->string('nama');
            $table->date('tgleff')->nullable();
            $table->integer('jw')->nullable();
            $table->date('tglexp')->nullable();
            $table->decimal('mdlawal', 15, 2)->default(0);
            $table->decimal('mgnawal', 15, 2)->default(0);
            $table->decimal('osmdlc', 15, 2)->default(0);
            $table->decimal('osmgnc', 15, 2)->default(0);
            $table->string('colbaru')->nullable();
            $table->string('kdaoh')->nullable();
            $table->string('acpok')->nullable();
            $table->decimal('angsmdl', 15, 2)->default(0);
            $table->decimal('angsmgn', 15, 2)->default(0);
            $table->text('alamat')->nullable();
            $table->string('telprmh')->nullable();
            $table->string('hp')->nullable();
            $table->string('fnama')->nullable();
            $table->decimal('sahirrp', 15, 2)->default(0);
            $table->decimal('tgkpok', 15, 2)->default(0);
            $table->decimal('tgkmgn', 15, 2)->default(0);
            $table->decimal('tgkdnd', 15, 2)->default(0);
            $table->integer('blntgkpok')->nullable();
            $table->integer('blntgkmgn')->nullable();
            $table->integer('blntgkdnd')->nullable();
            $table->string('kdkolek')->nullable();
            $table->string('kdgroupdeb')->nullable();
            $table->string('kdgroupdana')->nullable();
            $table->integer('haritgkmdl')->default(0);
            $table->integer('haritgkmgn')->default(0);
            $table->string('nocif')->nullable();
            $table->string('kdprd')->nullable();
            $table->string('pokpby')->nullable();
            $table->string('kdloc')->nullable();
            $table->string('kelurahan')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kota')->nullable();
            $table->string('nmao')->nullable();
            $table->string('colllanjut')->nullable();
            $table->integer('tgkharilanjut')->default(0);
            $table->integer('angs_ke')->default(0);
            $table->decimal('tagmdl', 15, 2)->default(0);
            $table->decimal('tagmgn', 15, 2)->default(0);
            $table->string('inptgl')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembiayaans');
    }
};
