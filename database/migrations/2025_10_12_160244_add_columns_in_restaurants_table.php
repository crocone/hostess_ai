<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('email', 54)->nullable();
            $table->string('phone', 54)->nullable();
            $table->string('country', 54)->nullable();
            $table->string('country_iso_code', 5)->nullable();
            $table->string('region', 124)->nullable();
            $table->string('city', 256)->nullable();
            $table->string('metro', 256)->nullable();
            $table->string('street', 256)->nullable();
            $table->string('house', 24)->nullable();
            $table->string('entrance', 124)->nullable();
            $table->string('floor', 12)->nullable();
            $table->string('flat', 12)->nullable();
            $table->string('room', 12)->nullable();
            $table->string('geo_lat', 24)->nullable();
            $table->string('geo_lon', 24)->nullable();
        });
    }
};
