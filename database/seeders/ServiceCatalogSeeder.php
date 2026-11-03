<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\VehicleCategory;
use Illuminate\Database\Seeder;

class ServiceCatalogSeeder extends Seeder
{
    /**
     * Catálogo de prueba: paquetes de lavado con precio/tiempo por categoría
     * y servicios adicionales de upselling.
     */
    public function run(): void
    {
        $categories = VehicleCategory::pluck('id', 'slug');

        // [precio, duración en minutos] por slug de categoría
        $packages = [
            [
                'name' => 'Lavado Ecológico en Seco',
                'description' => 'Limpieza y encerado en un solo paso con polímeros que encapsulan la suciedad. No requiere agua ni conexión eléctrica, así que funciona en cualquier estacionamiento o cochera.',
                'type' => 'Seco',
                'points' => 60,
                'sort_order' => 10,
                'price' => 220,
                'duration_minutes' => 60,
                'features' => [
                    'Exterior con polímeros y cera de carnauba',
                    'Aspirado de interiores y tapetes',
                    'Limpieza de cristales exteriores',
                    'Brillo y repelencia al polvo',
                    'Sin escurrimientos ni manchas',
                ],
                'variants' => [
                    'hatchback' => [200, 55],
                    'sedan' => [220, 60],
                    'suv' => [260, 70],
                    'pickup' => [290, 80],
                    'van-camioneta' => [300, 85],
                ],
            ],
            [
                'name' => 'Lavado con Agua de Bajo Consumo',
                'description' => 'Lavado profundo con hidrolavadora portátil, shampoo pH neutro y guantes de microfibra. Máxima eficacia con cerca de 15 litros por servicio frente a los 100 de una manguera.',
                'type' => 'Agua',
                'points' => 100,
                'sort_order' => 20,
                'price' => 260,
                'duration_minutes' => 85,
                'features' => [
                    'Carrocería con shampoo pH neutro',
                    'Lavado de llantas y rines',
                    'Abrillantado de plásticos exteriores',
                    'Aspirado profundo de interiores y cajuela',
                    'Lavado de tapetes incluido',
                ],
                'variants' => [
                    'hatchback' => [240, 70],
                    'sedan' => [260, 85],
                    'suv' => [300, 95],
                    'pickup' => [340, 110],
                    'van-camioneta' => [360, 115],
                ],
            ],
        ];

        foreach ($packages as $package) {
            $service = Service::updateOrCreate(
                ['name' => $package['name']],
                [
                    'description' => $package['description'],
                    'features' => $package['features'],
                    'price' => $package['price'],
                    'duration_minutes' => $package['duration_minutes'],
                    'type' => $package['type'],
                    'is_addon' => false,
                    'points' => $package['points'],
                    'sort_order' => $package['sort_order'],
                    'is_active' => true,
                ]
            );

            $variants = [];
            foreach ($package['variants'] as $slug => [$price, $duration]) {
                if ($categories->has($slug)) {
                    $variants[$categories[$slug]] = [
                        'price' => $price,
                        'duration_minutes' => $duration,
                    ];
                }
            }

            $service->vehicleCategories()->sync($variants);
        }

        $addons = [
            [
                'name' => 'Encerado protector',
                'description' => 'Capa de cera sintética que realza el brillo y protege la pintura del sol y la lluvia.',
                'price' => 180,
                'duration_minutes' => 20,
                'points' => 20,
                'sort_order' => 10,
            ],
            [
                'name' => 'Detallado de interiores',
                'description' => 'Limpieza profunda de tablero, consola, rejillas y zonas de contacto.',
                'price' => 320,
                'duration_minutes' => 45,
                'points' => 40,
                'sort_order' => 20,
            ],
            [
                'name' => 'Limpieza de vestiduras',
                'description' => 'Extracción de manchas en asientos y alfombra con productos de pH balanceado.',
                'price' => 450,
                'duration_minutes' => 60,
                'points' => 55,
                'sort_order' => 30,
            ],
            [
                'name' => 'Hidratación de piel',
                'description' => 'Acondicionador para piel natural o sintética: evita grietas y devuelve el tono.',
                'price' => 260,
                'duration_minutes' => 30,
                'points' => 30,
                'sort_order' => 40,
            ],
            [
                'name' => 'Desinfección con ozono',
                'description' => 'Elimina bacterias y olores del sistema de aire acondicionado y la cabina.',
                'price' => 240,
                'duration_minutes' => 25,
                'points' => 30,
                'sort_order' => 50,
            ],
            [
                'name' => 'Lavado de motor',
                'description' => 'Desengrase controlado con protección de componentes electrónicos.',
                'price' => 300,
                'duration_minutes' => 35,
                'points' => 35,
                'sort_order' => 60,
            ],
        ];

        foreach ($addons as $addon) {
            Service::updateOrCreate(
                ['name' => $addon['name']],
                [
                    ...$addon,
                    'type' => 'Seco',
                    'is_addon' => true,
                    'is_active' => true,
                ]
            );
        }
    }
}
