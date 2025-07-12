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
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Name of the menu item
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade'); // Foreign key to restaurants table
            $table->text('description')->nullable(); // Description of the menu item
            $table->integer('stock')->default(1); // Stock quantity of the menu item
            $table->string('image')->nullable(); // Image of the menu item
            $table->decimal('price', 16, 2); // Price of the menu
            $table->string('category'); // Category of the menu item (e.g., 'appetizer', 'main course', 'dessert', 'beverage')
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
