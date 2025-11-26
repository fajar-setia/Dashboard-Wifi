<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Hapus ini kalau kamu nggak mau user random:
        // User::factory(10)->create();

        // Buat user utama
        User::create([
            'name' => 'Baginda',
            'email' => 'admin@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('s0t0kudus'),
        ]);
    }
}
