<?php

namespace Database\Seeders;

use App\Enums\ListingType;
use App\Enums\PropertyStatus;
use App\Enums\UserRole;
use App\Models\Agent;
use App\Models\Category;
use App\Models\Property;
use App\Models\PropertyImage;
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

        $agent = Agent::query()->firstOrCreate(
            ['user_id' => $agentUser->id],
            [
                'title' => 'Senior Agent',
                'bio' => 'Helping you find the right property.',
                'company' => 'Demo Realty',
            ]
        );

        $residential = Category::query()->firstOrCreate(
            ['name' => 'Residential'],
            ['description' => 'Homes and apartments', 'sort_order' => 1]
        );

        $commercial = Category::query()->firstOrCreate(
            ['name' => 'Commercial'],
            ['description' => 'Offices and retail', 'sort_order' => 2]
        );

        [$p1, $p1Created] = $this->firstOrCreateProperty('Sunny Downtown Apartment', [
            'category_id' => $residential->id,
            'assigned_agent_id' => $agent->id,
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

        if ($p1Created) {
            $this->seedImages($p1->id, [
                'https://placehold.co/800x600/e8f4f8/2c7be5?text=Downtown+Apt+1',
                'https://placehold.co/800x600/f0f8e8/2c7be5?text=Downtown+Apt+2',
                'https://placehold.co/800x600/f8f0e8/2c7be5?text=Downtown+Apt+3',
            ]);
        }

        [$p2, $p2Created] = $this->firstOrCreateProperty('Waterfront Rental', [
            'category_id' => $residential->id,
            'assigned_agent_id' => $agent->id,
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

        if ($p2Created) {
            $this->seedImages($p2->id, [
                'https://placehold.co/800x600/e8f0f8/27ae60?text=Waterfront+1',
                'https://placehold.co/800x600/f8e8f0/27ae60?text=Waterfront+2',
            ]);
        }

        [$p3, $p3Created] = $this->firstOrCreateProperty('Corner Retail Unit', [
            'category_id' => $commercial->id,
            'assigned_agent_id' => $agent->id,
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

        if ($p3Created) {
            $this->seedImages($p3->id, [
                'https://placehold.co/800x600/f8f8e8/e67e22?text=Retail+Unit+1',
                'https://placehold.co/800x600/e8f8f8/e67e22?text=Retail+Unit+2',
                'https://placehold.co/800x600/f8e8f8/e67e22?text=Retail+Unit+3',
            ]);
        }

        $this->firstOrCreateProperty('Draft Listing (not public)', [
            'category_id' => $residential->id,
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

        $this->backfillMissingImages();
    }

    /** @return array{0: \App\Models\Property, 1: bool} */
    private function firstOrCreateProperty(string $title, array $attributes): array
    {
        $property = Property::query()->firstOrCreate(['title' => $title], $attributes);

        return [$property, $property->wasRecentlyCreated];
    }

    private function seedImages(int $propertyId, array $urls): void
    {
        foreach ($urls as $index => $url) {
            PropertyImage::query()->create([
                'property_id' => $propertyId,
                'path' => $url,
                'sort_order' => $index,
            ]);
        }
    }

    private function backfillMissingImages(): void
    {
        $placeholders = [
            'https://placehold.co/800x600/e8f4f8/2c7be5?text=Property+Image+1',
            'https://placehold.co/800x600/f0f8e8/2c7be5?text=Property+Image+2',
            'https://placehold.co/800x600/f8f0e8/2c7be5?text=Property+Image+3',
        ];

        Property::query()
            ->doesntHave('images')
            ->each(function (Property $property) use ($placeholders): void {
                foreach ($placeholders as $index => $url) {
                    PropertyImage::query()->create([
                        'property_id' => $property->id,
                        'path' => $url,
                        'sort_order' => $index,
                    ]);
                }
            });
    }
}
