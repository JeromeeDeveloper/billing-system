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
        Schema::create('special_billings', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id');
            $table->string('name');
            $table->decimal('amortization', 12, 2);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('gross', 12, 2)->nullable();
            $table->string('office')->nullable();
            $table->decimal('total_due', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('special_billings');
    }
};
