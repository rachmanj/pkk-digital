<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'email' => 'admin@pkk.test',
                'name' => 'Administrator',
                'password' => env('ADMIN_PASSWORD', 'password'),
            ]
        );
    }
}
