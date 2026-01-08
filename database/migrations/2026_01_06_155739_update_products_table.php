<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained();
            $table->enum('type', ['eyewear','sunglasses','lens','machinery']);
            $table->json('specifications')->nullable();
            $table->enum('approval_status', ['pending','approved','rejected'])
                  ->default('pending');
            $table->boolean('is_active')->default(true);
        });
    }
    
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'category_id',
                'type',
                'specifications',
                'approval_status',
                'is_active'
            ]);
        });
    }
    
};
