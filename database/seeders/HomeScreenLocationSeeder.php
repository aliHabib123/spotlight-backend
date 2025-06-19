<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\HomeScreenLocation;
use Illuminate\Support\Str;

class HomeScreenLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            [
                'name' => 'Top',
                'slug' => 'top',
                'display_order' => 1
            ],
            [
                'name' => 'Middle',
                'slug' => 'middle',
                'display_order' => 2
            ],
            [
                'name' => 'Bottom',
                'slug' => 'bottom',
                'display_order' => 3
            ],
        ];
        
        foreach ($locations as $location) {
            HomeScreenLocation::create($location);
        }
    }
}
