<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        \App\Models\User::firstOrCreate([
            'email' => 'admin@easygear.ng',
        ], [
            'name' => 'EasyGear Admin',
            'username' => 'admin',
            'password' => \Illuminate\Support\Facades\Hash::make('admin123'),
            'role' => 'admin',
            'phone_number' => '+2348012345678',
            'is_active' => true,
        ]);

        // Create test customer
        \App\Models\User::firstOrCreate([
            'email' => 'customer@test.com',
        ], [
            'name' => 'Test Customer',
            'username' => 'customer',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'customer',
            'phone_number' => '+2348087654321',
            'is_active' => true,
        ]);

        // Create test vendor
        \App\Models\User::firstOrCreate([
            'email' => 'vendor@test.com',
        ], [
            'name' => 'Test Vendor',
            'username' => 'vendor',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'vendor',
            'phone_number' => '+2348098765432',
            'is_active' => true,
        ]);
    }
}
