<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin User
        User::create([
            'name' => 'Jerome Porcado',
            'email' => 'porcadojerome@gmail.com',
            'password' => Hash::make('jerome123'),
            'role' => 'admin',
            'status' => 'approved',
            'branch_id' => null, // Admins may not need branch_id
        ]);

        // Branch User
        User::create([
            'name' => 'Test Branch',
            'email' => 'test@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'branch',
            'status' => 'approved',
            'branch_id' => null, // make sure this branch exists
        ]);
    }
}
