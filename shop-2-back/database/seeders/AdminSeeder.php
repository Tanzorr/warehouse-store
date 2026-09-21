<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@shop.test'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
            ]
        );

        // 'role' is not mass-assignable, set it explicitly.
        $admin->role = 'admin';
        $admin->save();
    }
}
