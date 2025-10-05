<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('menu_categories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->unsignedInteger('priority')->default(0);
            $t->timestamps();
            $t->unique(['restaurant_id','name']);
        });
        Schema::create('menu_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('menu_category_id')->constrained('menu_categories')->cascadeOnDelete();
            $t->string('name');
            $t->text('description')->nullable();
            $t->decimal('price', 10, 2);
            $t->boolean('available')->default(true);
            $t->json('options')->nullable();
            $t->timestamps();
            $t->index(['restaurant_id','menu_category_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menu_categories');
    }
};
