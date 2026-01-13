<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['vendor_id']);
            
            // Make vendor_id nullable
            $table->foreignId('vendor_id')->nullable()->change();
            
            // Re-add the foreign key constraint with nullable support
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['vendor_id']);
            
            // Make vendor_id required again
            $table->foreignId('vendor_id')->nullable(false)->change();
            
            // Re-add the foreign key constraint
            $table->foreign('vendor_id')->references('id')->on('vendors');
        });
    }
};
