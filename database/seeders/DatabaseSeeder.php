<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
            ]
        );

        User::query()->firstOrCreate(
            ['email' => 'buyer@example.com'],
            [
                'name' => 'Demo Buyer',
                'password' => Hash::make('password'),
                'role' => UserRole::User,
                'location' => 'Cairo',
            ]
        );

        $agentUser = User::query()->firstOrCreate(
            ['email' => 'agent@example.com'],
            [
                'name' => 'Demo Agent',
                'password' => Hash::make('password'),
                'role' => UserRole::User,
            ]
        );

        Agent::query()->firstOrCreate(
            ['user_id' => $agentUser->id],
            [
                'title' => 'Senior Agent',
                'bio' => 'Helping you find the right property.',
                'company' => 'Demo Realty',
            ]
        );

        $agent2User = User::query()->firstOrCreate(
            ['email' => 'agent2@example.com'],
            [
                'name' => 'Sara El-Masry',
                'password' => Hash::make('password'),
                'role' => UserRole::User,
            ]
        );

        Agent::query()->firstOrCreate(
            ['user_id' => $agent2User->id],
            [
                'title' => 'Luxury & New Cairo Lead',
                'bio' => 'Villas, compounds, and high-net-worth relocations.',
                'company' => 'Demo Realty',
            ]
        );

        $agent3User = User::query()->firstOrCreate(
            ['email' => 'agent3@example.com'],
            [
                'name' => 'Omar Hafez',
                'password' => Hash::make('password'),
                'role' => UserRole::User,
            ]
        );

        Agent::query()->firstOrCreate(
            ['user_id' => $agent3User->id],
            [
                'title' => 'Commercial & Retail',
                'bio' => 'Office floors, retail units, and logistics.',
                'company' => 'Demo Realty',
            ]
        );

        $this->call([
            CategorySeeder::class,
            PropertySeeder::class,
        ]);
    }
}
