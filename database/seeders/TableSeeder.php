<?php

namespace Database\Seeders;

use App\Models\Table;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tables = [
            ['id' => 1,  'capacity' => 2],
            ['id' => 2,  'capacity' => 2],
            ['id' => 3,  'capacity' => 2],
            ['id' => 4,  'capacity' => 6],
            ['id' => 5,  'capacity' => 4],
            ['id' => 6,  'capacity' => 6],
            ['id' => 7,  'capacity' => 6],
            ['id' => 8,  'capacity' => 2],
            ['id' => 9,  'capacity' => 2],
            ['id' => 10, 'capacity' => 2],
        ];

        foreach ($tables as $table) {
            Table::updateOrCreate(
                ['id' => $table['id']],
                [
                    'capacity' => $table['capacity'],
                    'status' => collect(['available', 'occupied', 'reserved'])->random(),
                    'restaurant_id' => 1, // change this if dynamic
                ]
            );
        }
    }
}
