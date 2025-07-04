<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Name of the restaurant
            $table->string('address'); // Address of the restaurant
            $table->string('phone'); // Phone number of the restaurant
            $table->string('email'); // Email address of the restaurant
            $table->string('description')->nullable(); // Description of the restaurant
            $table->string('logo')->nullable(); // Logo of the restaurant
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
