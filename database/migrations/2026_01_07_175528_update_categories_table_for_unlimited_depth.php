<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Remove unique constraint on slug to allow same category names under different parents
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Drop the unique constraint on slug
            $table->dropUnique(['slug']);
            
            // Create a composite unique index on slug and parent_id
            // This allows same slug under different parents, but prevents duplicates within same parent
            $table->unique(['slug', 'parent_id'], 'categories_slug_parent_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Drop the composite unique index
            $table->dropUnique('categories_slug_parent_unique');
            
            // Restore the original unique constraint on slug
            $table->unique('slug');
        });
    }
};
