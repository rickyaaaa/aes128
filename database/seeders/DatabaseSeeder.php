<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'owner'],
            [
                'name' => 'Dewi Kartika',
                'password' => 'password',
                'role' => 'owner',
                'is_active' => true,
            ],
        );

        User::updateOrCreate(
            ['username' => 'staff'],
            [
                'name' => 'Rafi Pratama',
                'password' => 'password',
                'role' => 'staff',
                'is_active' => true,
            ],
        );
    }
}
