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
        // API User - Token oluşturma için varsayılan kullanıcı
        User::firstOrCreate(
            ['email' => 'api@campuslist.com'],
            [
                'name' => 'API User',
                'password' => Hash::make('api_password_'.uniqid()),
            ]
        );

        // Test User (opsiyonel)
        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
