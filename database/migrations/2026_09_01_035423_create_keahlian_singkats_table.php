<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('keahlian_singkats', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon')->default('fas fa-code');
            $table->string('color')->default('#4E9BE0');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('keahlian_singkats');
    }
};