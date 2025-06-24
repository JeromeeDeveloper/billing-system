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
        Schema::create('loans_product', function (Blueprint $table) {
            $table->id();

            $table->string('product');
            $table->string('product_code')->nullable();
            $table->integer('prioritization')->nullable();
            $table->string('Status')->nullable();
            $table->string('billing_type')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans_product');
    }
};
