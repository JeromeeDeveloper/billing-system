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
        Schema::create('export_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('billing_period');
            $table->string('export_type'); // 'loans_savings', 'loans_savings_with_product', 'shares', 'shares_with_product'
            $table->timestamp('last_export_at')->nullable();
            $table->timestamp('last_upload_at')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            // Ensure unique combination of billing_period and export_type
            $table->unique(['billing_period', 'export_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_statuses');
    }
};
