<?php

namespace Database\Seeders;

use App\Enums\ListingType;
use App\Enums\PropertyStatus;
use App\Enums\UserRole;
use App\Models\Agent;
use App\Models\Category;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
        ]);

        User::query()->create([
            'name' => 'Demo Buyer',
            'email' => 'buyer@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::User,
            'location' => 'Cairo',
        ]);

        $agentUser = User::query()->create([
            'name' => 'Demo Agent',
            'email' => 'agent@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::User,
        ]);

        $agent = Agent::query()->create([
            'user_id' => $agentUser->id,
            'title' => 'Senior Agent',
            'bio' => 'Helping you find the right property.',
            'company' => 'Demo Realty',
        ]);

        $residential = Category::query()->create([
            'name' => 'Residential',
            'description' => 'Homes and apartments',
            'sort_order' => 1,
        ]);

        $commercial = Category::query()->create([
            'name' => 'Commercial',
            'description' => 'Offices and retail',
            'sort_order' => 2,
        ]);

        Property::query()->create([
            'category_id' => $residential->id,
            'assigned_agent_id' => $agent->id,
            'title' => 'Sunny Downtown Apartment',
            'description' => 'Bright 2-bedroom apartment near transit.',
            'price' => 325000,
            'listing_type' => ListingType::Sale,
            'bedrooms' => 2,
            'bathrooms' => 2,
            'kitchens' => 1,
            'status' => PropertyStatus::Published,
            'is_featured' => true,
            'sales_count' => 3,
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'address' => 'Downtown',
        ]);

        Property::query()->create([
            'category_id' => $residential->id,
            'assigned_agent_id' => $agent->id,
            'title' => 'Waterfront Rental',
            'description' => 'Furnished rental with marina views.',
            'price' => 2500,
            'listing_type' => ListingType::Rent,
            'bedrooms' => 3,
            'bathrooms' => 2,
            'kitchens' => 1,
            'status' => PropertyStatus::Published,
            'is_featured' => false,
            'sales_count' => 12,
            'latitude' => 30.0520,
            'longitude' => 31.2400,
            'address' => 'Waterfront',
        ]);

        Property::query()->create([
            'category_id' => $commercial->id,
            'assigned_agent_id' => $agent->id,
            'title' => 'Corner Retail Unit',
            'description' => 'High foot traffic corner space.',
            'price' => 890000,
            'listing_type' => ListingType::Sale,
            'bedrooms' => 0,
            'bathrooms' => 2,
            'kitchens' => 0,
            'status' => PropertyStatus::Published,
            'is_featured' => true,
            'sales_count' => 1,
            'latitude' => 30.0400,
            'longitude' => 31.2200,
            'address' => 'Main Street',
        ]);

        Property::query()->create([
            'category_id' => $residential->id,
            'title' => 'Draft Listing (not public)',
            'description' => 'Work in progress.',
            'price' => 100000,
            'listing_type' => ListingType::Sale,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'kitchens' => 1,
            'status' => PropertyStatus::Draft,
            'is_featured' => false,
            'sales_count' => 0,
        ]);
    }
}
