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
        Schema::table('special_billings', function (Blueprint $table) {
            $table->dropColumn('total_due');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('special_billings', function (Blueprint $table) {
            $table->decimal('total_due', 12, 2)->nullable();
        });
    }
};
