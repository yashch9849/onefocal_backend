<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Vendor;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get roles
        $vendorRole = Role::where('name', 'vendor')->first();
        $adminRole = Role::where('name', 'admin')->first();
        $customerRole = Role::where('name', 'customer')->first();

        // Create Vendor Users (1:1 relationship - vendor created with user_id)
        if ($vendorRole) {
            // Vendor 1
            $user1 = User::firstOrCreate(
                ['email' => 'vendor1@example.com'],
                [
                    'name' => 'John Vendor',
                    'email' => 'vendor1@example.com',
                    'password' => Hash::make('password'),
                    'role_id' => $vendorRole->id,
                    'approval_status' => 'approved',
                    'approved_at' => now(),
                ]
            );
            
            // Create vendor linked to user
            Vendor::firstOrCreate(
                ['user_id' => $user1->id],
                [
                    'user_id' => $user1->id,
                    'name' => 'ACME Corporation',
                    'slug' => 'acme-corp',
                    'status' => 'approved',
                ]
            );

            // Vendor 2
            $user2 = User::firstOrCreate(
                ['email' => 'vendor2@example.com'],
                [
                    'name' => 'Sarah Merchant',
                    'email' => 'vendor2@example.com',
                    'password' => Hash::make('password'),
                    'role_id' => $vendorRole->id,
                    'approval_status' => 'approved',
                    'approved_at' => now(),
                ]
            );
            
            Vendor::firstOrCreate(
                ['user_id' => $user2->id],
                [
                    'user_id' => $user2->id,
                    'name' => 'Tech Supplies Inc',
                    'slug' => 'tech-supplies',
                    'status' => 'approved',
                ]
            );

            // Vendor 3
            $user3 = User::firstOrCreate(
                ['email' => 'vendor3@example.com'],
                [
                    'name' => 'Mike Trader',
                    'email' => 'vendor3@example.com',
                    'password' => Hash::make('password'),
                    'role_id' => $vendorRole->id,
                    'approval_status' => 'approved',
                    'approved_at' => now(),
                ]
            );
            
            Vendor::firstOrCreate(
                ['user_id' => $user3->id],
                [
                    'user_id' => $user3->id,
                    'name' => 'Global Goods Ltd',
                    'slug' => 'global-goods',
                    'status' => 'approved',
                ]
            );
        }

        // Create Admin User (always approved)
        if ($adminRole) {
            $admin = User::firstOrCreate(
                ['email' => 'admin@example.com'],
                [
                    'name' => 'Admin User',
                    'email' => 'admin@example.com',
                    'password' => Hash::make('password'),
                    'role_id' => $adminRole->id,
                    'approval_status' => 'approved',
                    'approved_at' => now(),
                ]
            );
            
            // Ensure admin is always approved (in case it was created with pending status)
            if ($admin->approval_status !== 'approved') {
                $admin->update([
                    'approval_status' => 'approved',
                    'approved_at' => now(),
                ]);
            }
        }

        // Create Customer User
        if ($customerRole) {
            User::firstOrCreate(
                ['email' => 'customer@example.com'],
                [
                    'name' => 'Test Customer',
                    'email' => 'customer@example.com',
                    'password' => Hash::make('password'),
                    'role_id' => $customerRole->id,
                    'approval_status' => 'approved',
                ]
            );
        }
    }
}
