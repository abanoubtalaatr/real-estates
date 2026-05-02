<?php

namespace Database\Seeders;

use App\Enums\ListingType;
use App\Enums\PropertyStatus;
use App\Models\Agent;
use App\Models\Category;
use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    /**
     * Hotlinked Unsplash CDN URLs (buildings, villas, interiors, offices).
     * Kept short to stay within default string(255) for property_images.path.
     */
    private const IMAGE_VILLA = [
        'https://images.unsplash.com/photo-1613490493576-7fde63acd811?w=1000&q=80',
        'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=1000&q=80',
        'https://images.unsplash.com/photo-1600566753190-17fb0baa4869?w=1000&q=80',
        'https://images.unsplash.com/photo-1600585154526-990dced4db0d?w=1000&q=80',
        'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=1000&q=80',
        'https://images.unsplash.com/photo-1600566752355-35792bedcfea?w=1000&q=80',
        'https://images.unsplash.com/photo-1600573472592-401b489a3cdc?w=1000&q=80',
        'https://images.unsplash.com/photo-1600047509807-ba8f99d2cdde?w=1000&q=80',
    ];

    private const IMAGE_APARTMENT = [
        'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=1000&q=80',
        'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=1000&q=80',
        'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=1000&q=80',
        'https://images.unsplash.com/photo-1554995207-c18c203602cb?w=1000&q=80',
        'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=1000&q=80',
        'https://images.unsplash.com/photo-1536376072261-38c75010e6c9?w=1000&q=80',
        'https://images.unsplash.com/photo-1560185893-a55cbc8c57e8?w=1000&q=80',
        'https://images.unsplash.com/photo-1567767292278-a4f21aa2d36e?w=1000&q=80',
        'https://images.unsplash.com/photo-1493809842364-78817add7ffb?w=1000&q=80',
        'https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?w=1000&q=80',
    ];

    private const IMAGE_COMMERCIAL = [
        'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=1000&q=80',
        'https://images.unsplash.com/photo-1497366216548-37526070297c?w=1000&q=80',
        'https://images.unsplash.com/photo-1497366754035-f200968a6e72?w=1000&q=80',
        'https://images.unsplash.com/photo-1503387762-592deb58ef4e?w=1000&q=80',
        'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=1000&q=80',
        'https://images.unsplash.com/photo-1464938050520-ef2270bb8ce8?w=1000&q=80',
        'https://images.unsplash.com/photo-1497366811353-6870744d04b2?w=1000&q=80',
        'https://images.unsplash.com/photo-1489515217757-5fd1be406fef?w=1000&q=80',
    ];

    /** Luxury / penthouse — interiors & views */
    private const IMAGE_LUXURY = [
        'https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?w=1000&q=80',
        'https://images.unsplash.com/photo-1600566753086-00f18fb6b3ea?w=1000&q=80',
        'https://images.unsplash.com/photo-1600607687644-c7171b42498b?w=1000&q=80',
        'https://images.unsplash.com/photo-1600566752355-35792bedcfea?w=1000&q=80',
        'https://images.unsplash.com/photo-1600047509358-9dc75507daeb?w=1000&q=80',
        'https://images.unsplash.com/photo-1600573472550-8090b5e0745e?w=1000&q=80',
    ];

    public function run(): void
    {
        PropertyImage::query()->where('path', 'like', '%placehold%')->delete();

        $residential = Category::query()->firstOrCreate(
            ['name' => 'Residential'],
            ['description' => 'Apartments, family homes, and urban living', 'sort_order' => 1]
        );

        $commercial = Category::query()->firstOrCreate(
            ['name' => 'Commercial'],
            ['description' => 'Offices, retail, and mixed-use', 'sort_order' => 2]
        );

        $villas = Category::query()->firstOrCreate(
            ['name' => 'Villas & Estates'],
            ['description' => 'Detached villas, compounds, and large homes', 'sort_order' => 3]
        );

        $luxury = Category::query()->firstOrCreate(
            ['name' => 'Penthouses & Luxury'],
            ['description' => 'Top-floor units, duplexes, and high-end finishes', 'sort_order' => 4]
        );

        $agents = Agent::query()->orderBy('id')->get();
        $pickAgent = static function (int $i) use ($agents): ?int {
            if ($agents->isEmpty()) {
                return null;
            }

            return $agents[$i % $agents->count()]->id;
        };

        $definitions = $this->propertyDefinitions(
            $residential->id,
            $commercial->id,
            $villas->id,
            $luxury->id,
            $pickAgent
        );

        foreach ($definitions as $index => $row) {
            $style = $row['image_style'];
            unset($row['image_style']);

            [$property, $created] = $this->firstOrCreateProperty($row['title'], $row);
            if ($created || $property->images()->doesntExist()) {
                $this->seedImages($property->id, $this->pickImages($style, $index));
            }
        }

        $this->seedGeneratedBatch($residential, $commercial, $villas, $luxury, $pickAgent);
        $this->backfillMissingImages();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function propertyDefinitions(
        int $residentialId,
        int $commercialId,
        int $villasId,
        int $luxuryId,
        callable $pickAgent
    ): array {
        $cairo = fn (float $lat, float $lng): array => ['latitude' => $lat, 'longitude' => $lng];

        return [
            [
                'title' => 'Sunny Downtown Apartment',
                'category_id' => $residentialId,
                'assigned_agent_id' => $pickAgent(0),
                'description' => 'Bright corner unit with floor-to-ceiling windows, open kitchen, and walk-in closets. Steps from cafés and metro.',
                'price' => 325000,
                'listing_type' => ListingType::Sale,
                'bedrooms' => 2,
                'bathrooms' => 2,
                'kitchens' => 1,
                'status' => PropertyStatus::Published,
                'is_featured' => true,
                'sales_count' => 3,
                'address' => 'Downtown Cairo — Talaat Harb',
                ...$cairo(30.0444, 31.2357),
                'image_style' => 'apartment',
            ],
            [
                'title' => 'Waterfront Rental',
                'category_id' => $residentialId,
                'assigned_agent_id' => $pickAgent(1),
                'description' => 'Fully furnished rental with Nile outlooks, concierge, and gym access. Flexible lease terms.',
                'price' => 2500,
                'listing_type' => ListingType::Rent,
                'bedrooms' => 3,
                'bathrooms' => 2,
                'kitchens' => 1,
                'status' => PropertyStatus::Published,
                'is_featured' => false,
                'sales_count' => 12,
                'address' => 'Zamalek — waterfront strip',
                ...$cairo(30.052, 31.24),
                'image_style' => 'apartment',
            ],
            [
                'title' => 'Corner Retail Unit',
                'category_id' => $commercialId,
                'assigned_agent_id' => $pickAgent(2),
                'description' => 'Ground-floor retail with dual street frontage, heavy foot traffic, and dedicated loading.',
                'price' => 890000,
                'listing_type' => ListingType::Sale,
                'bedrooms' => 0,
                'bathrooms' => 2,
                'kitchens' => 0,
                'status' => PropertyStatus::Published,
                'is_featured' => true,
                'sales_count' => 1,
                'address' => 'Maadi — Road 9 retail corridor',
                ...$cairo(29.96, 31.25),
                'image_style' => 'commercial',
            ],
            [
                'title' => 'Draft Listing (not public)',
                'category_id' => $residentialId,
                'assigned_agent_id' => null,
                'description' => 'Work in progress — photos and copy being finalized.',
                'price' => 100000,
                'listing_type' => ListingType::Sale,
                'bedrooms' => 1,
                'bathrooms' => 1,
                'kitchens' => 1,
                'status' => PropertyStatus::Draft,
                'is_featured' => false,
                'sales_count' => 0,
                'address' => null,
                'latitude' => null,
                'longitude' => null,
                'image_style' => 'apartment',
            ],
            [
                'title' => 'New Cairo Garden Villa — Type A',
                'category_id' => $villasId,
                'assigned_agent_id' => $pickAgent(3),
                'description' => 'Standalone villa with private garden, maid’s room, and covered parking for three cars. Gated community with clubhouse.',
                'price' => 18500000,
                'listing_type' => ListingType::Sale,
                'bedrooms' => 5,
                'bathrooms' => 4,
                'kitchens' => 2,
                'status' => PropertyStatus::Published,
                'is_featured' => true,
                'sales_count' => 7,
                'address' => 'New Cairo — Fifth Settlement',
                ...$cairo(30.02, 31.48),
                'image_style' => 'villa',
            ],
            [
                'title' => 'Compound Twin Villa — Corner',
                'category_id' => $villasId,
                'assigned_agent_id' => $pickAgent(4),
                'description' => 'Twin villa on a corner plot with side garden, roof terrace, and smart-home wiring.',
                'price' => 14200000,
                'listing_type' => ListingType::Sale,
                'bedrooms' => 4,
                'bathrooms' => 3,
                'kitchens' => 1,
                'status' => PropertyStatus::Published,
                'is_featured' => false,
                'sales_count' => 2,
                'address' => 'Sheikh Zayed — West Cairo',
                ...$cairo(30.07, 31.02),
                'image_style' => 'villa',
            ],
            [
                'title' => 'Marina North Coast Villa (Summer)',
                'category_id' => $villasId,
                'assigned_agent_id' => $pickAgent(5),
                'description' => 'Beach-close villa with pool, outdoor kitchen, and split AC. Ideal summer lease; winter negotiable.',
                'price' => 45000,
                'listing_type' => ListingType::Rent,
                'bedrooms' => 4,
                'bathrooms' => 3,
                'kitchens' => 1,
                'status' => PropertyStatus::Published,
                'is_featured' => true,
                'sales_count' => 28,
                'address' => 'North Coast — Marina strip',
                ...$cairo(30.85, 28.95),
                'image_style' => 'villa',
            ],
            [
                'title' => 'Zamalek Classic Penthouse',
                'category_id' => $luxuryId,
                'assigned_agent_id' => $pickAgent(6),
                'description' => 'Duplex penthouse with wraparound terrace, marble finishes, and unobstructed Nile views.',
                'price' => 52000000,
                'listing_type' => ListingType::Sale,
                'bedrooms' => 4,
                'bathrooms' => 4,
                'kitchens' => 2,
                'status' => PropertyStatus::Published,
                'is_featured' => true,
                'sales_count' => 0,
                'address' => 'Zamalek — 26th July axis',
                ...$cairo(30.062, 31.222),
                'image_style' => 'luxury',
            ],
            [
                'title' => 'Heliopolis Art Deco Apartment',
                'category_id' => $residentialId,
                'assigned_agent_id' => $pickAgent(7),
                'description' => 'High ceilings, original parquet, updated electrics. Quiet tree-lined street near Korba.',
                'price' => 4100000,
                'listing_type' => ListingType::Sale,
                'bedrooms' => 3,
                'bathrooms' => 2,
                'kitchens' => 1,
                'status' => PropertyStatus::Published,
                'is_featured' => false,
                'sales_count' => 5,
                'address' => 'Heliopolis — Korba',
                ...$cairo(30.09, 31.32),
                'image_style' => 'apartment',
            ],
            [
                'title' => '6th October Office Floor Plate',
                'category_id' => $commercialId,
                'assigned_agent_id' => $pickAgent(8),
                'description' => 'Full floor open plan with raised flooring, backup generator slot, and reserved parking ratio.',
                'price' => 12500000,
                'listing_type' => ListingType::Sale,
                'bedrooms' => 0,
                'bathrooms' => 4,
                'kitchens' => 1,
                'status' => PropertyStatus::Published,
                'is_featured' => false,
                'sales_count' => 0,
                'address' => '6th October — Central Axis',
                ...$cairo(29.97, 30.94),
                'image_style' => 'commercial',
            ],
            [
                'title' => 'Maadi Degla Family Duplex',
                'category_id' => $residentialId,
                'assigned_agent_id' => $pickAgent(9),
                'description' => 'Split-level duplex with garden access, two living rooms, and quiet Degla proximity.',
                'price' => 9800000,
                'listing_type' => ListingType::Sale,
                'bedrooms' => 4,
                'bathrooms' => 3,
                'kitchens' => 2,
                'status' => PropertyStatus::Published,
                'is_featured' => true,
                'sales_count' => 4,
                'address' => 'Maadi — Degla',
                ...$cairo(29.96, 31.26),
                'image_style' => 'apartment',
            ],
            [
                'title' => 'Smart Studio — Rehab City',
                'category_id' => $residentialId,
                'assigned_agent_id' => $pickAgent(10),
                'description' => 'Compact studio with built-ins, ideal for remote work. Near Rehab gates and retail.',
                'price' => 1850000,
                'listing_type' => ListingType::Sale,
                'bedrooms' => 1,
                'bathrooms' => 1,
                'kitchens' => 1,
                'status' => PropertyStatus::Published,
                'is_featured' => false,
                'sales_count' => 11,
                'address' => 'New Cairo — Rehab City',
                ...$cairo(30.051, 31.491),
                'image_style' => 'apartment',
            ],
            [
                'title' => 'Flagship Showroom — Ring Road',
                'category_id' => $commercialId,
                'assigned_agent_id' => $pickAgent(11),
                'description' => 'Double-height showroom, glass facade, truck access. Suited to automotive or retail flagship.',
                'price' => 22000000,
                'listing_type' => ListingType::Sale,
                'bedrooms' => 0,
                'bathrooms' => 3,
                'kitchens' => 0,
                'status' => PropertyStatus::Published,
                'is_featured' => true,
                'sales_count' => 0,
                'address' => 'South Ring Road — showroom strip',
                ...$cairo(30.01, 31.38),
                'image_style' => 'commercial',
            ],
            [
                'title' => 'Katameya Heights Golf Villa',
                'category_id' => $villasId,
                'assigned_agent_id' => $pickAgent(12),
                'description' => 'Golf-front villa with pool, outdoor lounge, and basement entertainment room.',
                'price' => 35000000,
                'listing_type' => ListingType::Sale,
                'bedrooms' => 6,
                'bathrooms' => 5,
                'kitchens' => 2,
                'status' => PropertyStatus::Published,
                'is_featured' => true,
                'sales_count' => 2,
                'address' => 'New Cairo — Katameya Heights',
                ...$cairo(30.005, 31.422),
                'image_style' => 'villa',
            ],
            [
                'title' => 'Giza Nile View Rental — Long Term',
                'category_id' => $residentialId,
                'assigned_agent_id' => $pickAgent(13),
                'description' => 'Three-bedroom rental with partial Nile view, semi-furnished, pets negotiable.',
                'price' => 18000,
                'listing_type' => ListingType::Rent,
                'bedrooms' => 3,
                'bathrooms' => 2,
                'kitchens' => 1,
                'status' => PropertyStatus::Published,
                'is_featured' => false,
                'sales_count' => 9,
                'address' => 'Giza — Dokki approach',
                ...$cairo(30.038, 31.21),
                'image_style' => 'apartment',
            ],
            [
                'title' => 'New Administrative Capital Tower Floor',
                'category_id' => $commercialId,
                'assigned_agent_id' => $pickAgent(14),
                'description' => 'Half-floor shell & core in a prime tower; district cooling, high-speed lifts.',
                'price' => 48000000,
                'listing_type' => ListingType::Sale,
                'bedrooms' => 0,
                'bathrooms' => 6,
                'kitchens' => 1,
                'status' => PropertyStatus::Published,
                'is_featured' => true,
                'sales_count' => 0,
                'address' => 'New Capital — CBD',
                ...$cairo(29.98, 31.73),
                'image_style' => 'commercial',
            ],
            [
                'title' => 'Garden City Diplomatic Apartment',
                'category_id' => $luxuryId,
                'assigned_agent_id' => $pickAgent(15),
                'description' => 'Large reception, herringbone floors, embassy district calm.',
                'price' => 28500000,
                'listing_type' => ListingType::Sale,
                'bedrooms' => 3,
                'bathrooms' => 3,
                'kitchens' => 2,
                'status' => PropertyStatus::Published,
                'is_featured' => false,
                'sales_count' => 1,
                'address' => 'Garden City',
                ...$cairo(30.035, 31.231),
                'image_style' => 'luxury',
            ],
            [
                'title' => 'October Logistics Warehouse Shell',
                'category_id' => $commercialId,
                'assigned_agent_id' => $pickAgent(16),
                'description' => 'Clear-span warehouse, high bay, three dock doors, yard for trailers.',
                'price' => 42000000,
                'listing_type' => ListingType::Sale,
                'bedrooms' => 0,
                'bathrooms' => 2,
                'kitchens' => 0,
                'status' => PropertyStatus::Published,
                'is_featured' => false,
                'sales_count' => 0,
                'address' => '6th October — industrial zone',
                ...$cairo(29.93, 30.89),
                'image_style' => 'commercial',
            ],
            [
                'title' => 'El Gouna Lagoon Villa',
                'category_id' => $villasId,
                'assigned_agent_id' => $pickAgent(17),
                'description' => 'Lagoon-front villa with private dock option, pool, and outdoor dining.',
                'price' => 22500000,
                'listing_type' => ListingType::Sale,
                'bedrooms' => 4,
                'bathrooms' => 4,
                'kitchens' => 1,
                'status' => PropertyStatus::Published,
                'is_featured' => true,
                'sales_count' => 3,
                'address' => 'El Gouna — lagoon district',
                ...$cairo(27.39, 33.68),
                'image_style' => 'villa',
            ],
            [
                'title' => 'Nasr City Furnished Short Stay',
                'category_id' => $residentialId,
                'assigned_agent_id' => $pickAgent(18),
                'description' => 'Two-bedroom turnkey unit near hub malls; ideal corporate stays.',
                'price' => 9500,
                'listing_type' => ListingType::Rent,
                'bedrooms' => 2,
                'bathrooms' => 2,
                'kitchens' => 1,
                'status' => PropertyStatus::Published,
                'is_featured' => false,
                'sales_count' => 33,
                'address' => 'Nasr City — Olympic axis',
                ...$cairo(30.051, 31.34),
                'image_style' => 'apartment',
            ],
            [
                'title' => 'Alexandria Seafront Condo',
                'category_id' => $luxuryId,
                'assigned_agent_id' => $pickAgent(19),
                'description' => 'Sea-view condo with wide balcony, storm shutters, and basement parking.',
                'price' => 7800000,
                'listing_type' => ListingType::Sale,
                'bedrooms' => 3,
                'bathrooms' => 2,
                'kitchens' => 1,
                'status' => PropertyStatus::Published,
                'is_featured' => false,
                'sales_count' => 6,
                'address' => 'Alexandria — Stanley',
                ...$cairo(31.22, 29.91),
                'image_style' => 'luxury',
            ],
        ];
    }

    private function seedGeneratedBatch(
        Category $residential,
        Category $commercial,
        Category $villas,
        Category $luxury,
        callable $pickAgent
    ): void {
        $faker = fake('en_US');
        $templates = [
            ['cat' => $villas, 'style' => 'villa', 'beds' => [4, 7], 'baths' => [3, 6], 'sale' => [9_000_000, 45_000_000], 'rent' => [25_000, 85_000]],
            ['cat' => $residential, 'style' => 'apartment', 'beds' => [1, 4], 'baths' => [1, 3], 'sale' => [1_200_000, 9_000_000], 'rent' => [4_000, 22_000]],
            ['cat' => $commercial, 'style' => 'commercial', 'beds' => [0, 0], 'baths' => [1, 4], 'sale' => [6_000_000, 35_000_000], 'rent' => [15_000, 120_000]],
            ['cat' => $luxury, 'style' => 'luxury', 'beds' => [3, 5], 'baths' => [3, 5], 'sale' => [12_000_000, 65_000_000], 'rent' => [18_000, 45_000]],
        ];

        for ($i = 0; $i < 36; $i++) {
            $t = $templates[$i % count($templates)];
            $listing = ($i % 5 !== 0) ? ListingType::Sale : ListingType::Rent;
            $beds = $faker->numberBetween($t['beds'][0], $t['beds'][1]);
            $baths = max(1, $faker->numberBetween($t['baths'][0], $t['baths'][1]));
            $price = $listing === ListingType::Sale
                ? $faker->numberBetween($t['sale'][0], $t['sale'][1])
                : $faker->numberBetween($t['rent'][0], $t['rent'][1]);

            $title = sprintf(
                'Catalog Listing #%04d — %s %s',
                2100 + $i,
                $faker->city(),
                $faker->randomElement(['Residence', 'Tower', 'Plaza', 'Gardens', 'Heights'])
            );
            $lat = $faker->randomFloat(7, 27.5, 31.3);
            $lng = $faker->randomFloat(7, 28.5, 33.8);

            $attributes = [
                'category_id' => $t['cat']->id,
                'assigned_agent_id' => $pickAgent(20 + $i),
                'description' => $faker->paragraphs(3, true),
                'price' => $price,
                'listing_type' => $listing,
                'bedrooms' => $beds,
                'bathrooms' => $baths,
                'kitchens' => $faker->numberBetween(1, 2),
                'status' => ($i % 17 === 0) ? PropertyStatus::Draft : PropertyStatus::Published,
                'is_featured' => $i % 9 === 0,
                'sales_count' => $faker->numberBetween(0, 40),
                'latitude' => $lat,
                'longitude' => $lng,
                'address' => $faker->streetAddress().', '.$faker->randomElement(['Cairo', 'Giza', 'Alexandria', 'New Cairo', '6th October']),
            ];

            [$property, $created] = $this->firstOrCreateProperty($title, $attributes);
            $style = $t['style'];
            if ($created || $property->images()->doesntExist()) {
                $this->seedImages($property->id, $this->pickImages($style, $i + 100));
            }
        }
    }

    /** @return array{0: Property, 1: bool} */
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

    /**
     * @return list<string>
     */
    private function pickImages(string $style, int $index): array
    {
        $pool = match ($style) {
            'villa' => self::IMAGE_VILLA,
            'commercial' => self::IMAGE_COMMERCIAL,
            'luxury' => self::IMAGE_LUXURY,
            default => self::IMAGE_APARTMENT,
        };

        $n = count($pool);
        $want = 5;
        $start = $index % max(1, $n - $want + 1);
        $out = [];
        for ($k = 0; $k < $want; $k++) {
            $out[] = $pool[($start + $k) % $n];
        }

        return $out;
    }

    private function backfillMissingImages(): void
    {
        $fallback = array_merge(
            array_slice(self::IMAGE_APARTMENT, 0, 2),
            array_slice(self::IMAGE_VILLA, 0, 2),
            [self::IMAGE_COMMERCIAL[0]]
        );

        Property::query()
            ->doesntHave('images')
            ->each(function (Property $property) use ($fallback): void {
                foreach ($fallback as $index => $url) {
                    PropertyImage::query()->create([
                        'property_id' => $property->id,
                        'path' => $url,
                        'sort_order' => $index,
                    ]);
                }
            });
    }
}
