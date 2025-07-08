<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Menu;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Menu::create([
            'name' => 'Nasi Goreng',
            'description' => 'Fried rice with vegetables and chicken',
            'category' => 'Main Course',
            'price' => 15000,
            'restaurant_id' => 1, // Assuming restaurant with ID 1 exists
            'image' => 'nasi_goreng.jpg',
        ]);
    }
}
