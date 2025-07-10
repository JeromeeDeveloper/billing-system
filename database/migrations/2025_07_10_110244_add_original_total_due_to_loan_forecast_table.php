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
        Schema::table('loan_forecast', function (Blueprint $table) {
            $table->decimal('original_total_due', 12, 2)->nullable()->after('total_due')->comment('Original forecast amount before any remittance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_forecast', function (Blueprint $table) {
            $table->dropColumn('original_total_due');
        });
    }
};
