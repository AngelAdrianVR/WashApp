<?php

namespace Database\Seeders;

use App\Models\VehicleCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VehicleCategorySeeder extends Seeder
{
    /**
     * Categorías de vehículo con las que se calcula precio y duración.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Hatchback', 'sort_order' => 10],
            ['name' => 'Sedán', 'sort_order' => 20],
            ['name' => 'SUV', 'sort_order' => 30],
            ['name' => 'Pickup', 'sort_order' => 40],
            ['name' => 'Van / Camioneta', 'sort_order' => 50],
        ];

        foreach ($categories as $category) {
            VehicleCategory::updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'sort_order' => $category['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
