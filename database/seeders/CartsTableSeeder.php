<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cart;
use App\Models\User;
use App\Models\Role;

class CartsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customerRole = Role::where('name', 'customer')->first();

        if (!$customerRole) {
            return; // Skip if customer role doesn't exist
        }

        $customers = User::where('role_id', $customerRole->id)->get();

        if ($customers->isEmpty()) {
            return; // Skip if no customers exist
        }

        // Create a cart for each customer
        foreach ($customers as $customer) {
            Cart::firstOrCreate(
                ['user_id' => $customer->id]
            );
        }
    }
}
