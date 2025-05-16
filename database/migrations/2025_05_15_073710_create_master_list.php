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
        Schema::create('master_list', function (Blueprint $table) {
            $table->id();

            $table->foreignId('branches_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->foreignId('member_id')->nullable()->constrained('members')->onDelete('cascade');
            $table->foreignId('collection_id')->nullable()->constrained('collection')->onDelete('cascade');
            $table->foreignId('bill_id')->nullable()->constrained('bill')->onDelete('cascade');
            $table->foreignId('remittance_id')->nullable()->constrained('remittance')->onDelete('cascade');
            $table->foreignId('loan_forecast_id')->nullable()->constrained('loan_forecast')->onDelete('cascade');
            $table->foreignId('atm_module_id')->nullable()->constrained('atm_module')->onDelete('cascade');
            $table->string('status');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_list');
    }
};
