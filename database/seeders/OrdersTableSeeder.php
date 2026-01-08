<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Role;

class OrdersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customerRole = Role::where('name', 'customer')->first();
        $vendors = Vendor::all();

        if (!$customerRole || $vendors->isEmpty()) {
            return; // Skip if customer role or vendors don't exist
        }

        $customers = User::where('role_id', $customerRole->id)->get();

        if ($customers->isEmpty()) {
            return; // Skip if no customers exist
        }

        // Create 2-4 orders
        $orderCount = rand(2, 4);
        $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

        for ($i = 0; $i < $orderCount; $i++) {
            $customer = $customers->random();
            $vendor = $vendors->random();

            // Note: Migration uses 'total' but model has 'total_amount' in fillable
            // Using 'total' to match database column
            Order::create([
                'vendor_id' => $vendor->id,
                'user_id' => $customer->id,
                'total' => round(rand(5000, 50000) / 100, 2), // Random total between 50.00 and 500.00
                'status' => $statuses[array_rand($statuses)],
            ]);
        }
    }
}
