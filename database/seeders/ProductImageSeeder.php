<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductImage;

class ProductImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::all();
        
        $gymProductImages = [
            'gym-equipment/barbell.jpg',
            'gym-equipment/dumbbells.jpg', 
            'gym-equipment/treadmill.jpg',
            'gym-equipment/power-rack.jpg',
            'gym-equipment/yoga-mat.jpg',
            'gym-equipment/resistance-bands.jpg',
            'gym-equipment/kettlebell.jpg',
            'gym-equipment/bench.jpg',
            'gym-equipment/spinning-bike.jpg',
            'gym-equipment/medicine-ball.jpg',
            'gym-equipment/pull-up-band.jpg',
            'gym-equipment/foam-roller.jpg',
            'gym-equipment/weight-plates.jpg',
            'gym-equipment/battle-ropes.jpg',
            'gym-equipment/floor-mats.jpg',
            'gym-equipment/protein-shaker.jpg',
            'gym-equipment/cable-machine.jpg',
            'gym-equipment/hex-dumbbells.jpg',
            'gym-equipment/plyo-boxes.jpg',
            'gym-equipment/lifting-platform.jpg',
        ];

        foreach ($products as $index => $product) {
            // Skip if product already has images
            if ($product->images()->count() === 0) {
                $imagePath = $gymProductImages[$index] ?? 'gym-equipment/default.jpg';
                
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $imagePath,
                    'alt_text' => $product->name,
                    'sort_order' => 1
                ]);
            }
        }
        
        $this->command->info('Created images for all products without existing images!');
    }
}
