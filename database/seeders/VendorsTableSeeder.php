<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Vendor;

class VendorsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Vendors are now created in UsersTableSeeder with user_id
        // This seeder is kept for backward compatibility but vendors
        // should be created through user registration or UsersTableSeeder
    }
}
