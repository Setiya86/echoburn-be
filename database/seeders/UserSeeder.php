<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Admin
        User::create([
            'nama_lengkap'  => 'Administrator', // Sesuaikan dengan migrasi
            'email'         => 'admin@example.com',
            'password'      => Hash::make('password123'),
            'alamat'        => 'Jl. Admin Pusat No. 1',
            'nomor_telepon' => '081234567890',
        ]);

        // 2. User Biasa
        User::create([
            'nama_lengkap'  => 'User Demo', // Sesuaikan dengan migrasi
            'email'         => 'user@example.com',
            'password'      => Hash::make('password123'),
            'alamat'        => 'Jl. Warga No. 10',
            'nomor_telepon' => '089876543210',
        ]);

        // 3. Dummy User (Factory)
        // Pastikan UserFactory juga sudah diperbaiki (lihat poin di bawah)
        User::factory()->count(5)->create();
    }
}