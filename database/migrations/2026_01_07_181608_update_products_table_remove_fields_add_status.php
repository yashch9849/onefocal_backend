<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Remove: slug, type, specifications, approval_status, is_active
     * Add: status field
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Remove fields that are no longer needed
            if (Schema::hasColumn('products', 'slug')) {
                $table->dropColumn('slug');
            }
            if (Schema::hasColumn('products', 'type')) {
                $table->dropColumn('type');
            }
            if (Schema::hasColumn('products', 'specifications')) {
                $table->dropColumn('specifications');
            }
            if (Schema::hasColumn('products', 'approval_status')) {
                $table->dropColumn('approval_status');
            }
            if (Schema::hasColumn('products', 'is_active')) {
                $table->dropColumn('is_active');
            }
            
            // Add status field
            $table->string('status')->default('active')->after('moq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Restore removed fields
            $table->string('slug')->nullable();
            $table->enum('type', ['eyewear','sunglasses','lens','machinery'])->nullable();
            $table->json('specifications')->nullable();
            $table->enum('approval_status', ['pending','approved','rejected'])->default('pending');
            $table->boolean('is_active')->default(true);
            
            // Remove status
            $table->dropColumn('status');
        });
    }
};
