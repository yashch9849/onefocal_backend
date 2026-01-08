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
        // Approve all existing admin users
        \App\Models\User::whereHas('role', function ($query) {
            $query->where('name', 'admin');
        })->update([
            'approval_status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set admin users back to pending (optional - usually not needed)
        \App\Models\User::whereHas('role', function ($query) {
            $query->where('name', 'admin');
        })->update([
            'approval_status' => 'pending',
            'approved_at' => null,
        ]);
    }
};
