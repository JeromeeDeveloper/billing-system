<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the ENUM to include 'atm' value
        DB::statement("ALTER TABLE saving_products MODIFY COLUMN product_type ENUM('mortuary', 'regular', 'atm') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original ENUM values
        DB::statement("ALTER TABLE saving_products MODIFY COLUMN product_type ENUM('mortuary', 'regular') NULL");
    }
};
