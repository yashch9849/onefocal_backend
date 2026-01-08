<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Enforces 1:1 relationship: One vendor = One user
     */
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            // Add user_id column (nullable first to migrate existing data)
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->onDelete('cascade');
        });

        // Migrate existing data: For each vendor, find the first user with that vendor_id
        // and assign it to the vendor
        $vendors = DB::table('vendors')->get();
        foreach ($vendors as $vendor) {
            $user = DB::table('users')->where('vendor_id', $vendor->id)->first();
            if ($user) {
                DB::table('vendors')
                    ->where('id', $vendor->id)
                    ->update(['user_id' => $user->id]);
            }
        }

        // Now make user_id required and unique
        Schema::table('vendors', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
