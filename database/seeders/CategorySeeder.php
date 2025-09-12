<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Football',
                'description' => 'Football equipment and gear',
                'slug' => 'football',
                'parent_id' => null,
                'subcategories' => [
                    ['name' => 'Football Boots', 'slug' => 'football-boots'],
                    ['name' => 'Jerseys', 'slug' => 'football-jerseys'],
                    ['name' => 'Shorts', 'slug' => 'football-shorts'],
                    ['name' => 'Socks', 'slug' => 'football-socks'],
                    ['name' => 'Goalkeeper Gloves', 'slug' => 'goalkeeper-gloves'],
                ]
            ],
            [
                'name' => 'Basketball',
                'description' => 'Basketball equipment and gear',
                'slug' => 'basketball',
                'parent_id' => null,
                'subcategories' => [
                    ['name' => 'Basketball Shoes', 'slug' => 'basketball-shoes'],
                    ['name' => 'Jerseys', 'slug' => 'basketball-jerseys'],
                    ['name' => 'Shorts', 'slug' => 'basketball-shorts'],
                    ['name' => 'Basketballs', 'slug' => 'basketballs'],
                ]
            ],
            [
                'name' => 'Tennis',
                'description' => 'Tennis equipment and gear',
                'slug' => 'tennis',
                'parent_id' => null,
                'subcategories' => [
                    ['name' => 'Tennis Rackets', 'slug' => 'tennis-rackets'],
                    ['name' => 'Tennis Shoes', 'slug' => 'tennis-shoes'],
                    ['name' => 'Tennis Balls', 'slug' => 'tennis-balls'],
                    ['name' => 'Tennis Apparel', 'slug' => 'tennis-apparel'],
                ]
            ],
            [
                'name' => 'Running',
                'description' => 'Running equipment and gear',
                'slug' => 'running',
                'parent_id' => null,
                'subcategories' => [
                    ['name' => 'Running Shoes', 'slug' => 'running-shoes'],
                    ['name' => 'Running Apparel', 'slug' => 'running-apparel'],
                    ['name' => 'Running Accessories', 'slug' => 'running-accessories'],
                ]
            ],
            [
                'name' => 'Fitness',
                'description' => 'Fitness equipment and gear',
                'slug' => 'fitness',
                'parent_id' => null,
                'subcategories' => [
                    ['name' => 'Gym Equipment', 'slug' => 'gym-equipment'],
                    ['name' => 'Weights', 'slug' => 'weights'],
                    ['name' => 'Fitness Apparel', 'slug' => 'fitness-apparel'],
                ]
            ]
        ];

        foreach ($categories as $categoryData) {
            $category = \App\Models\Category::firstOrCreate([
                'name' => $categoryData['name'],
                'parent_id' => $categoryData['parent_id'],
            ], [
                'description' => $categoryData['description'],
                'slug' => $categoryData['slug'],
            ]);

            if (isset($categoryData['subcategories'])) {
                foreach ($categoryData['subcategories'] as $subcategory) {
                    \App\Models\Category::firstOrCreate([
                        'name' => $subcategory['name'],
                        'parent_id' => $category->id,
                    ], [
                        'description' => $subcategory['name'] . ' for ' . $categoryData['name'],
                        'slug' => $subcategory['slug'],
                    ]);
                }
            }
        }
    }
}
