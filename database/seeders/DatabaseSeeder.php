<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'restaurantA',
        //     'email' => 'restaurantA@example.com',
        // ]);

        User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@test.com',
        ]);

        // $this->call([
        //     RestaurantSeeder::class,
        //     MenuSeeder::class,
        //     TableSeeder::class,
        // ]);
    }
}
