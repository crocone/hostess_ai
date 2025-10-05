<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('reservations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $t->string('guest_name');
            $t->string('guest_phone');
            $t->dateTime('start_at');
            $t->dateTime('end_at');
            $t->unsignedSmallInteger('guests')->default(2);
            $t->enum('status', ['pending','confirmed','cancelled'])->default('confirmed')->index();
            $t->text('notes')->nullable();
            $t->string('source')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('idempotency_key')->nullable()->unique();
            $t->timestamps();
            $t->index(['restaurant_id','start_at']);
        });

        Schema::create('reservation_tables', function (Blueprint $t) {
            $t->id();
            $t->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $t->foreignId('table_id')->constrained('tables')->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['reservation_id','table_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('reservation_tables');
        Schema::dropIfExists('reservations');
    }
};
