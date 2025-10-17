<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Administrador
        User::create([
            'name' => 'Angel Vazquez',
            'email' => 'angelvazquez470@gmail.com',
            'password' => '321321321', // Laravel hasheará esto automáticamente gracias a la configuración en tu modelo User.
            'phone_number' => '3312155731',
            'role' => 'Admin',
        ]);

        // Empleado
        User::create([
            'name' => 'Empleado',
            'email' => 'empleado@gmail.com',
            'password' => '321321321',
            'phone_number' => '3333034738',
            'role' => 'Empleado',
        ]);

        // Cliente
        User::create([
            'name' => 'Cliente',
            'email' => 'cliente@gmail.com',
            'password' => '321321321',
            'phone_number' => '3216549877',
            'role' => 'Cliente',
        ]);
    }
}
