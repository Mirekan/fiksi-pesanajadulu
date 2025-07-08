<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Restaurant;

class RestaurantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Restaurant::create([
            'name' => 'Restaurant A',
            'address' => 'Banguntapan, Bantul, Yogyakarta',
            'phone' => '081234567890',
            'email' => 'restaurantA@example.com',
            'description' => 'A cozy restaurant serving delicious food.',
            'logo' => 'logoA.png',
        ]);

    }
}
