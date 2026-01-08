<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Create product_variant_attributes table for flexible EAV pattern
     * Stores attributes like color, frame_size, frame_type, lens_type, etc.
     */
    public function up(): void
    {
        Schema::create('product_variant_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->string('attribute_name'); // e.g., 'color', 'frame_size', 'frame_type', 'lens_type'
            $table->string('attribute_value'); // e.g., 'black', 'medium', 'full_rim', 'progressive'
            $table->timestamps();
            
            // Index for faster lookups
            $table->index(['product_variant_id', 'attribute_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variant_attributes');
    }
};
