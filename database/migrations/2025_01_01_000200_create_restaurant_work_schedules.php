<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('restaurant_work_schedules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('weekday');
            $t->time('open_at')->nullable();
            $t->time('close_at')->nullable();
            $t->boolean('is_closed')->default(false);
            $t->timestamps();
            $t->unique(['restaurant_id','weekday']);
        });
        Schema::create('restaurant_work_exceptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $t->date('date');
            $t->time('open_at')->nullable();
            $t->time('close_at')->nullable();
            $t->boolean('is_closed')->default(false);
            $t->timestamps();
            $t->unique(['restaurant_id','date']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('restaurant_work_exceptions');
        Schema::dropIfExists('restaurant_work_schedules');
    }
};
