<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('confirm_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->enum('send_type', ['email', 'phone'])->index();
            $table->string('email', 512)->nullable()->index();
            $table->string('phone', 20)->nullable()->index();
            $table->string('code', 10);
            $table->enum('type', ['auth', 'confirm', 'forgot'])->index();
            $table->timestamp('expires_at')->index();
            $table->boolean('used')->default(false);
            $table->ipAddress();
            $table->timestamps();

            $table->index(['phone', 'type']);
            $table->index(['email', 'type']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('phone')->after('email')->nullable();
            $table->string('name')->nullable()->change();
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
        Schema::dropIfExists('confirm_codes');
    }
};
