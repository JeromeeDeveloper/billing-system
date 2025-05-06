<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Jerome Porcado',
            'email' => 'porcadojerome@gmail.com',
            'password' => Hash::make('jerome123'), // You can change the password
        ]);
    }
}
