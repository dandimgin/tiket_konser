<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'Admin')->firstOrFail();

        User::updateOrCreate(
            ['email' => 'admin@tiketkonser.test'],
            [
                'role_id' => $adminRole->id,
                'name' => 'Admin Tiket Konser',
                'password' => 'admin12345',
                'phone' => null,
            ]
        );
    }
}