<?php

namespace Database\Seeders;

use App\Models\CampaignCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CampaignCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Medical & Health',       'icon' => '🏥', 'sort_order' => 1],
            ['name' => 'Education',               'icon' => '📚', 'sort_order' => 2],
            ['name' => 'Emergency Relief',        'icon' => '🆘', 'sort_order' => 3],
            ['name' => 'Community & Nonprofits',  'icon' => '🤝', 'sort_order' => 4],
            ['name' => 'Animals & Pets',          'icon' => '🐾', 'sort_order' => 5],
            ['name' => 'Environment',             'icon' => '🌱', 'sort_order' => 6],
            ['name' => 'Creative Projects',       'icon' => '🎨', 'sort_order' => 7],
            ['name' => 'Sports & Recreation',     'icon' => '⚽', 'sort_order' => 8],
            ['name' => 'Business & Startups',     'icon' => '💼', 'sort_order' => 9],
            ['name' => 'Family & Children',       'icon' => '👨‍👩‍👧', 'sort_order' => 10],
            ['name' => 'Memorials & Funerals',    'icon' => '🕊️', 'sort_order' => 11],
            ['name' => 'Other',                   'icon' => '📋', 'sort_order' => 99],
        ];

        foreach ($categories as $category) {
            CampaignCategory::updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'icon' => $category['icon'],
                    'is_active' => true,
                    'sort_order' => $category['sort_order'],
                ]
            );
        }
    }
}
