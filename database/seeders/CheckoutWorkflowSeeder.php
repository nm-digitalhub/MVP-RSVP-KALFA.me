<?php

namespace Database\Seeders;

use App\Models\CartItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CheckoutWorkflowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder creates sample cart items for testing the checkout workflow.
     * It creates cart items for the test user to demonstrate the workflow.
     */
    public function run(): void
    {
        $user = User::where('email', 'test@example.com')->first();

        if (!$user) {
            $this->command->warn('Test user not found. Creating test user first...');
            $user = User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }

        // Clear existing cart items for the test user
        CartItem::where('user_id', $user->id)->delete();

        // Create sample cart items
        $products = [
            [
                'product_name' => 'Premium Wireless Headphones',
                'quantity' => 1,
                'price' => 149.99,
            ],
            [
                'product_name' => 'Smart Watch Series X',
                'quantity' => 2,
                'price' => 299.99,
            ],
            [
                'product_name' => 'USB-C Charging Cable (3-Pack)',
                'quantity' => 1,
                'price' => 19.99,
            ],
            [
                'product_name' => 'Portable Power Bank 20000mAh',
                'quantity' => 1,
                'price' => 45.99,
            ],
            [
                'product_name' => 'Bluetooth Speaker - Waterproof',
                'quantity' => 3,
                'price' => 79.99,
            ],
        ];

        foreach ($products as $product) {
            CartItem::create([
                'user_id' => $user->id,
                'session_id' => session()->id() ?? Str::uuid(),
                'product_name' => $product['product_name'],
                'quantity' => $product['quantity'],
                'price' => $product['price'],
            ]);
        }

        $this->command->info('Created ' . count($products) . ' cart items for test user');
    }
}
