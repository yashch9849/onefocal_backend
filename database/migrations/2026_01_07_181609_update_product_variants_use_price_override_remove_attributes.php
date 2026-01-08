<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Remove: color, frame_type, attributes JSON
     * Rename: price -> price_override (nullable)
     */
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            // Remove color and frame_type if they exist (from previous migration)
            if (Schema::hasColumn('product_variants', 'color')) {
                $table->dropColumn('color');
            }
            if (Schema::hasColumn('product_variants', 'frame_type')) {
                $table->dropColumn('frame_type');
            }
            
            // Remove attributes JSON column
            if (Schema::hasColumn('product_variants', 'attributes')) {
                $table->dropColumn('attributes');
            }
        });
        
        // Rename price to price_override using raw SQL (avoids doctrine/dbal requirement)
        if (Schema::hasColumn('product_variants', 'price') && !Schema::hasColumn('product_variants', 'price_override')) {
            // Use raw SQL for column rename (works across different database drivers)
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE product_variants CHANGE price price_override DECIMAL(10,2) NULL');
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE product_variants RENAME COLUMN price TO price_override');
                DB::statement('ALTER TABLE product_variants ALTER COLUMN price_override DROP NOT NULL');
            } else {
                // SQLite or others - drop and recreate
                Schema::table('product_variants', function (Blueprint $table) {
                    $table->decimal('price_override', 10, 2)->nullable()->after('sku');
                });
                DB::statement('UPDATE product_variants SET price_override = price');
                Schema::table('product_variants', function (Blueprint $table) {
                    $table->dropColumn('price');
                });
            }
        } elseif (Schema::hasColumn('product_variants', 'price_override')) {
            // Column already renamed, just make it nullable
            Schema::table('product_variants', function (Blueprint $table) {
                $table->decimal('price_override', 10, 2)->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Make price_override required first
        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('price_override', 10, 2)->nullable(false)->change();
        });
        
        // Rename price_override back to price using raw SQL
        if (Schema::hasColumn('product_variants', 'price_override') && !Schema::hasColumn('product_variants', 'price')) {
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE product_variants CHANGE price_override price DECIMAL(10,2) NOT NULL');
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE product_variants RENAME COLUMN price_override TO price');
                DB::statement('ALTER TABLE product_variants ALTER COLUMN price SET NOT NULL');
            } else {
                // SQLite or others - drop and recreate
                Schema::table('product_variants', function (Blueprint $table) {
                    $table->decimal('price', 10, 2)->nullable(false)->after('sku');
                });
                DB::statement('UPDATE product_variants SET price = price_override');
                Schema::table('product_variants', function (Blueprint $table) {
                    $table->dropColumn('price_override');
                });
            }
        }
        
        // Restore other columns
        Schema::table('product_variants', function (Blueprint $table) {
            // Restore color and frame_type
            $table->string('color')->after('product_id');
            $table->string('frame_type')->nullable()->after('color');
            
            // Restore attributes JSON
            $table->json('attributes')->nullable();
        });
    }
};
