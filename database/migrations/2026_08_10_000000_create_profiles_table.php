<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
         
            $table->string('foto_profil')->nullable();
            $table->string('nama_lengkap')->nullable();
            $table->string('judul_profesi')->nullable();
            $table->text('bio_singkat')->nullable();
            $table->string('email_publik')->nullable();
            $table->string('nomor_telepon')->nullable();
            $table->string('alamat_lokasi')->nullable();
            
            // Kolom badge
            $table->string('teks_badge_1')->nullable();
            $table->string('teks_badge_2')->nullable();
            // 
            $table->text('skills')->nullable(); 
        
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('profiles');
    }
};