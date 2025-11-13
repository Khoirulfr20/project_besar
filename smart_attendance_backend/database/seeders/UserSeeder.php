<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin Default
        User::updateOrCreate(
            ['email' => 'admin@smartattendance.com'],
            [
                'employee_id' => 'ADM001',
                'name' => 'Super Admin',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'phone' => '081234567890',
                'position' => 'System Administrator',
                'department' => 'IT',
                'is_active' => true,
            ]
        );

        // Pimpinan Default
        User::updateOrCreate(
            ['email' => 'pimpinan@smartattendance.com'],
            [
                'employee_id' => 'PIM001',
                'name' => 'John Doe',
                'password' => Hash::make('pimpinan123'),
                'role' => 'pimpinan',
                'phone' => '081234567891',
                'position' => 'Manager',
                'department' => 'Management',
                'is_active' => true,
            ]
        );

        // Anggota Default
        User::updateOrCreate(
            ['email' => 'anggota@smartattendance.com'],
            [
                'employee_id' => 'ANG001',
                'name' => 'Jane Smith',
                'password' => Hash::make('anggota123'),
                'role' => 'anggota',
                'phone' => '081234567892',
                'position' => 'Staff',
                'department' => 'Operations',
                'is_active' => true,
            ]
        );
    }
}