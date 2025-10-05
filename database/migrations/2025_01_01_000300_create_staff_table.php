<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('staff', function (Blueprint $t) {
            $t->id();
            $t->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->string('phone')->nullable();
            $t->enum('role', ['waiter','hostess','manager'])->default('waiter');
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('staff_work_shifts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $t->date('date');
            $t->time('start_at');
            $t->time('end_at');
            $t->timestamps();
            $t->index(['staff_id','date']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('staff_work_shifts');
        Schema::dropIfExists('staff');
    }
};
