<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'name' => 'Rent',
                'description' => 'Properties available to lease and short-term stays.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Buy',
                'description' => 'Homes and units listed for purchase.',
                'sort_order' => 2,
            ],
            [
                'name' => 'House',
                'description' => 'Villas, townhouses, and standalone family homes.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Appointment',
                'description' => 'Commercial spaces and listings that typically need a scheduled viewing.',
                'sort_order' => 4,
            ],
        ];

        foreach ($rows as $row) {
            Category::query()->firstOrCreate(
                ['name' => $row['name']],
                [
                    'description' => $row['description'],
                    'sort_order' => $row['sort_order'],
                ]
            );
        }
    }
}
