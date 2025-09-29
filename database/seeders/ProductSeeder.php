<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\Vendor;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create Gym category
        $gymCategory = Category::firstOrCreate(
            ['name' => 'Gym & Fitness'],
            [
                'slug' => 'gym-fitness',
                'description' => 'Professional gym equipment and fitness accessories',
            ]
        );

        // Get or create a vendor for gym products
        $gymVendor = Vendor::firstOrCreate(
            ['name' => 'FitGear Pro'],
            [
                'contact_email' => 'info@fitgearpro.com',
                'contact_phone' => '+2348012345678',
                'address' => '123 Fitness Street, Victoria Island, Lagos',
                'is_active' => true,
                'commission_rate' => 15.00,
            ]
        );

        // Gym products data
        $gymProducts = [
            [
                'name' => 'Olympic Barbell Set 20kg',
                'short_description' => 'Professional Olympic standard barbell for serious weightlifting',
                'description' => 'High-quality Olympic barbell made from premium steel with excellent grip and durability. Perfect for deadlifts, squats, bench press, and other compound movements. Includes locking collars and meets international standards.',
                'price' => 45000.00,
                'quantity' => 15,
                'weight' => 20.00,
                'sku' => 'GYM-BAR-OLY-20',
                'is_featured' => true,
            ],
            [
                'name' => 'Adjustable Dumbbell Set 50kg',
                'short_description' => 'Space-saving adjustable dumbbells for home and gym use',
                'description' => 'Complete adjustable dumbbell set with weight plates ranging from 2.5kg to 25kg per dumbbell. Easy to adjust weight settings with secure locking mechanism. Perfect for all fitness levels.',
                'price' => 32000.00,
                'quantity' => 20,
                'weight' => 50.00,
                'sku' => 'GYM-DUM-ADJ-50',
                'is_featured' => true,
            ],
            [
                'name' => 'Professional Treadmill TM-5000',
                'short_description' => 'Commercial grade treadmill with advanced features',
                'description' => 'High-performance treadmill with 3HP motor, speeds up to 18km/h, 15 incline levels, and 12 preset programs. Features heart rate monitoring, LCD display, and safety key.',
                'price' => 450000.00,
                'quantity' => 5,
                'weight' => 85.00,
                'sku' => 'GYM-TRD-TM5000',
                'is_featured' => true,
            ],
            [
                'name' => 'Power Rack with Pull-up Bar',
                'short_description' => 'Heavy-duty power rack for safe and effective training',
                'description' => 'Commercial grade power rack with adjustable safety bars, pull-up bar, and weight plate storage. Built from heavy gauge steel for maximum safety and durability.',
                'price' => 125000.00,
                'quantity' => 8,
                'weight' => 120.00,
                'sku' => 'GYM-PWR-RACK-01',
                'is_featured' => false,
            ],
            [
                'name' => 'Yoga Mat Premium 6mm',
                'short_description' => 'Non-slip premium yoga mat for all practice levels',
                'description' => 'Eco-friendly yoga mat made from natural rubber with excellent grip and cushioning. Perfect for yoga, pilates, and general fitness exercises. Includes carrying strap.',
                'price' => 8500.00,
                'quantity' => 50,
                'weight' => 1.20,
                'sku' => 'GYM-YOG-MAT-6MM',
                'is_featured' => false,
            ],
            [
                'name' => 'Resistance Bands Set',
                'short_description' => 'Complete resistance training system with multiple bands',
                'description' => 'Professional resistance bands set with 5 different resistance levels, door anchor, handles, ankle straps, and workout guide. Perfect for strength training and rehabilitation.',
                'price' => 12000.00,
                'quantity' => 30,
                'weight' => 2.00,
                'sku' => 'GYM-RES-BAND-SET',
                'is_featured' => false,
            ],
            [
                'name' => 'Kettlebell Cast Iron 16kg',
                'short_description' => 'Professional cast iron kettlebell for functional training',
                'description' => 'High-quality cast iron kettlebell with wide handle for comfortable grip. Perfect for swing, snatch, clean & press, and other kettlebell exercises.',
                'price' => 18000.00,
                'quantity' => 25,
                'weight' => 16.00,
                'sku' => 'GYM-KET-CI-16KG',
                'is_featured' => false,
            ],
            [
                'name' => 'Bench Press Adjustable',
                'short_description' => 'Multi-position adjustable bench for versatile workouts',
                'description' => 'Heavy-duty adjustable bench with multiple incline positions. Supports up to 300kg load capacity. Perfect for bench press, incline press, and various dumbbell exercises.',
                'price' => 55000.00,
                'quantity' => 12,
                'weight' => 45.00,
                'sku' => 'GYM-BEN-ADJ-01',
                'is_featured' => false,
            ],
            [
                'name' => 'Spinning Bike Pro X1',
                'short_description' => 'Professional spinning bike for intense cardio workouts',
                'description' => 'Commercial grade spinning bike with magnetic resistance, adjustable seat and handlebars, LCD monitor, and built-in heart rate sensor. Quiet operation.',
                'price' => 95000.00,
                'quantity' => 10,
                'weight' => 48.00,
                'sku' => 'GYM-SPN-BK-X1',
                'is_featured' => true,
            ],
            [
                'name' => 'Medicine Ball 10kg',
                'short_description' => 'Durable medicine ball for functional strength training',
                'description' => 'Heavy-duty medicine ball with textured surface for secure grip. Perfect for slam exercises, rotational movements, and partner workouts.',
                'price' => 15000.00,
                'quantity' => 35,
                'weight' => 10.00,
                'sku' => 'GYM-MED-BAL-10KG',
                'is_featured' => false,
            ],
            [
                'name' => 'Pull-up Assist Band',
                'short_description' => 'Heavy-duty resistance band for assisted pull-ups',
                'description' => 'Premium latex resistance band designed for pull-up assistance and stretching. Multiple resistance levels available. Helps build up to unassisted pull-ups.',
                'price' => 6500.00,
                'quantity' => 40,
                'weight' => 0.50,
                'sku' => 'GYM-PUL-ASS-BND',
                'is_featured' => false,
            ],
            [
                'name' => 'Foam Roller 90cm',
                'short_description' => 'High-density foam roller for muscle recovery',
                'description' => 'Professional foam roller for myofascial release and muscle recovery. High-density foam maintains shape over time. Essential for post-workout recovery.',
                'price' => 9500.00,
                'quantity' => 25,
                'weight' => 0.80,
                'sku' => 'GYM-FOM-ROL-90CM',
                'is_featured' => false,
            ],
            [
                'name' => 'Weight Plates Set 100kg',
                'short_description' => 'Olympic standard weight plates set',
                'description' => 'Complete set of Olympic weight plates: 2x25kg, 2x20kg, 2x15kg, 2x10kg, 2x5kg, 2x2.5kg. Cast iron construction with precise weight tolerance.',
                'price' => 75000.00,
                'quantity' => 8,
                'weight' => 100.00,
                'sku' => 'GYM-WGT-PLT-100KG',
                'is_featured' => true,
            ],
            [
                'name' => 'Battle Ropes 15m',
                'short_description' => 'Heavy battle ropes for high-intensity training',
                'description' => 'Professional battle ropes made from high-quality manila fiber. 15 meters long, 38mm diameter. Perfect for HIIT workouts and building functional strength.',
                'price' => 22000.00,
                'quantity' => 15,
                'weight' => 12.00,
                'sku' => 'GYM-BAT-ROP-15M',
                'is_featured' => false,
            ],
            [
                'name' => 'Gym Flooring Mats 60x60cm',
                'short_description' => 'Interlocking gym flooring for home and commercial use',
                'description' => 'High-quality interlocking rubber gym mats. 60x60cm tiles, 12mm thick. Excellent shock absorption and durability. Easy to install and maintain.',
                'price' => 3500.00,
                'quantity' => 100,
                'weight' => 2.50,
                'sku' => 'GYM-FLR-MAT-60X60',
                'is_featured' => false,
            ],
            [
                'name' => 'Protein Shaker Bottle 600ml',
                'short_description' => 'Premium protein shaker with mixing ball',
                'description' => 'BPA-free protein shaker bottle with stainless steel mixing ball. Leak-proof design with measurement markings. Perfect for protein shakes and supplements.',
                'price' => 2500.00,
                'quantity' => 80,
                'weight' => 0.30,
                'sku' => 'GYM-SHK-BOT-600ML',
                'is_featured' => false,
            ],
            [
                'name' => 'Cable Machine Multi-Station',
                'short_description' => 'Commercial cable machine for total body workouts',
                'description' => 'Professional cable machine with dual weight stacks, multiple attachment points, and adjustable pulleys. Perfect for lat pulldowns, cable rows, and functional movements.',
                'price' => 285000.00,
                'quantity' => 3,
                'weight' => 250.00,
                'sku' => 'GYM-CAB-MAC-MS01',
                'is_featured' => true,
            ],
            [
                'name' => 'Hex Dumbbells Set 5-50kg',
                'short_description' => 'Complete set of hex dumbbells for commercial use',
                'description' => 'Professional hex dumbbell set from 5kg to 50kg in 5kg increments. Rubber-coated to protect floors and reduce noise. Includes storage rack.',
                'price' => 350000.00,
                'quantity' => 2,
                'weight' => 275.00,
                'sku' => 'GYM-HEX-DUM-SET',
                'is_featured' => true,
            ],
            [
                'name' => 'Plyometric Box Set',
                'short_description' => 'Wooden plyometric boxes for jump training',
                'description' => 'Set of 3 wooden plyo boxes (20", 24", 30") made from high-quality plywood. Perfect for box jumps, step-ups, and plyometric training.',
                'price' => 28000.00,
                'quantity' => 18,
                'weight' => 25.00,
                'sku' => 'GYM-PLY-BOX-SET',
                'is_featured' => false,
            ],
            [
                'name' => 'Olympic Weight Lifting Platform',
                'short_description' => 'Professional weightlifting platform for Olympic lifts',
                'description' => 'Competition-grade weightlifting platform with shock-absorbing rubber surface and hardwood center. 8x8 feet platform perfect for deadlifts, snatches, and cleans.',
                'price' => 185000.00,
                'quantity' => 4,
                'weight' => 150.00,
                'sku' => 'GYM-OLY-PLT-8X8',
                'is_featured' => true,
            ],
        ];

        // Create products
        foreach ($gymProducts as $productData) {
            $slug = Str::slug($productData['name']) . '-' . time() . '-' . rand(100, 999);
            
            Product::firstOrCreate([
                'name' => $productData['name'],
                'vendor_id' => $gymVendor->id,
            ], [
                'category_id' => $gymCategory->id,
                'vendor_id' => $gymVendor->id,
                'name' => $productData['name'],
                'slug' => $slug,
                'short_description' => $productData['short_description'],
                'description' => $productData['description'],
                'price' => $productData['price'],
                'quantity' => $productData['quantity'],
                'weight' => $productData['weight'],
                'sku' => $productData['sku'],
                'is_active' => true,
                'status' => 'active',
                'is_featured' => $productData['is_featured'],
                'average_rating' => rand(35, 50) / 10, // Random rating between 3.5 and 5.0
                'total_reviews' => rand(5, 50),
                'total_sales' => rand(10, 200),
                'view_count' => rand(50, 500),
            ]);
        }

        $this->command->info('Created 20 gym products successfully!');
    }
}
