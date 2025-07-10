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
        Schema::table('saving_products', function (Blueprint $table) {
            $table->decimal('amount_to_deduct', 12, 2)->nullable()->after('product_code');
            $table->integer('prioritization')->nullable()->after('amount_to_deduct');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saving_products', function (Blueprint $table) {
            $table->dropColumn(['amount_to_deduct', 'prioritization']);
        });
    }
};
