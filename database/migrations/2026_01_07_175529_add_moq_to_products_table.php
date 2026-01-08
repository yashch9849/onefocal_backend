<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add MOQ (Minimum Order Quantity) field and make category_id required
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Add MOQ field
            $table->integer('moq')->default(1)->after('price');
            
            // Make category_id required (products must belong to a category)
            $table->foreignId('category_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('moq');
            $table->foreignId('category_id')->nullable()->change();
        });
    }
};
