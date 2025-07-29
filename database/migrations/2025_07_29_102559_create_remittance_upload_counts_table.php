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
        Schema::create('remittance_upload_counts', function (Blueprint $table) {
            $table->id();
            $table->string('billing_period');
            $table->string('upload_type'); // 'regular', 'special', 'shares'
            $table->integer('count')->default(0);
            $table->timestamps();

            // Ensure unique combination of billing_period and upload_type
            $table->unique(['billing_period', 'upload_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remittance_upload_counts');
    }
};
