<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Get current date for billing period
        // Note: During seeding, there's no authenticated admin user,
        // so we use current date. In production, new users added via
        // the admin interface will inherit the admin's billing period.
        $currentBillingPeriod = Carbon::now()->format('Y-m-01');

        // Admin User
        User::create([
            'name' => 'Jerome Porcado',
            'email' => 'porcadojerome@gmail.com',
            'password' => Hash::make('jerome123'),
            'role' => 'admin',
            'status' => 'approved',
            'branch_id' => null, // Admins may not need branch_id
            'billing_period' => $currentBillingPeriod,
        ]);

        // Branch User
        User::create([
            'name' => 'Test Branch',
            'email' => 'test@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'branch',
            'status' => 'pending',
            'branch_id' => null, // make sure this branch exists
            'billing_period' => $currentBillingPeriod,
        ]);
    }
}
