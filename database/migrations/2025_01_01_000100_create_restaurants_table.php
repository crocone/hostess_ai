<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('restaurants', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('phone')->nullable();
            $t->string('timezone')->default('Europe/Moscow');
            $t->json('settings')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('restaurants'); }
};
