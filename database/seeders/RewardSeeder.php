<?php

namespace Database\Seeders;

use App\Models\Reward;
use Illuminate\Database\Seeder;

class RewardSeeder extends Seeder
{
    /**
     * Premios de ejemplo que los usuarios pueden canjear con sus puntos.
     * Las imágenes se guardan con Spatie Media Library (colección "image").
     */
    public function run(): void
    {
        $rewards = [
            [
                'name' => 'Lavado Ecológico Gratis',
                'description' => 'Un lavado ecológico en seco completo para tu auto, sin costo y en la puerta de tu casa.',
                'points_cost' => 500,
                'image' => 'images/rewards/lavado-gratis.svg',
            ],
            [
                'name' => 'Encerado Protector Express',
                'description' => 'Capa de cera sintética que realza el brillo y protege la pintura del sol y la lluvia.',
                'points_cost' => 300,
                'image' => 'images/rewards/encerado-express.svg',
            ],
            [
                'name' => 'Detallado de Interiores',
                'description' => 'Limpieza profunda de tablero, consola, rejillas y zonas de contacto de tu cabina.',
                'points_cost' => 650,
                'image' => 'images/rewards/detallado-interiores.svg',
            ],
            [
                'name' => 'Desinfección con Ozono',
                'description' => 'Elimina bacterias y olores del sistema de aire acondicionado y de toda la cabina.',
                'points_cost' => 400,
                'image' => 'images/rewards/desinfeccion-ozono.svg',
            ],
            [
                'name' => 'Kit de Microfibras Premium',
                'description' => 'Set de paños de microfibra de alta densidad para mantener tu auto impecable entre lavados.',
                'points_cost' => 250,
                'image' => 'images/rewards/kit-microfibras.svg',
            ],
            [
                'name' => 'Membresía Equilibrio · 1 mes',
                'description' => 'Un mes completo del plan Equilibrio: 4 lavados, 1 encerado express y soporte preferente.',
                'points_cost' => 1200,
                'image' => 'images/rewards/membresia-equilibrio.svg',
            ],
        ];

        foreach ($rewards as $reward) {
            $image = $reward['image'];
            unset($reward['image']);

            $model = Reward::updateOrCreate(
                ['name' => $reward['name']],
                [...$reward, 'is_active' => true]
            );

            $path = public_path($image);

            if (! $model->hasMedia('image') && file_exists($path)) {
                $model->addMedia($path)
                    ->preservingOriginal()
                    ->usingFileName(basename($path))
                    ->toMediaCollection('image');
            }
        }
    }
}
