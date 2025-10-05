<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('halls', function (Blueprint $t) {
            $t->id();
            $t->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->unsignedInteger('priority')->default(0);
            $t->timestamps();
        });
        Schema::create('zones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('hall_id')->constrained('halls')->cascadeOnDelete();
            $t->string('name');
            $t->unsignedInteger('priority')->default(0);
            $t->timestamps();
        });
        Schema::create('tables', function (Blueprint $t) {
            $t->id();
            $t->foreignId('zone_id')->constrained('zones')->cascadeOnDelete();
            $t->string('code');
            $t->unsignedTinyInteger('seats')->default(2);
            $t->boolean('is_active')->default(true);
            $t->json('attributes')->nullable();
            $t->timestamps();
            $t->unique(['zone_id','code']);
        });
        Schema::create('staff_zone', function (Blueprint $t) {
            $t->id();
            $t->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $t->foreignId('zone_id')->constrained('zones')->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['staff_id','zone_id']);
        });
        Schema::create('staff_table', function (Blueprint $t) {
            $t->id();
            $t->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $t->foreignId('table_id')->constrained('tables')->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['staff_id','table_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('staff_table');
        Schema::dropIfExists('staff_zone');
        Schema::dropIfExists('tables');
        Schema::dropIfExists('zones');
        Schema::dropIfExists('halls');
    }
};
