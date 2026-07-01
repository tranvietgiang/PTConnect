<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'email' => 'system@ptconnect.edu.vn',
            'role' => 'system_admin',
        ]);

        User::factory()->create([
            'email' => 'admin@ptconnect.edu.vn',
            'role' => 'school_admin',
        ]);

        User::factory()->create([
            'email' => 'teacher@ptconnect.edu.vn',
            'role' => 'teacher',
        ]);

        User::factory()->create([
            'email' => 'assistant@ptconnect.edu.vn',
            'role' => 'assistant',
        ]);

        User::factory()->create([
            'email' => 'student@ptconnect.edu.vn',
            'role' => 'student',
        ]);
    }
}
