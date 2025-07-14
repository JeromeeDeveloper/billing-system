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
        Schema::create('contra_acc', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['shares', 'savings', 'loans']);
            $table->string('account_number')->nullable(); // for shares/savings
            $table->string('loan_acc_no')->nullable(); // for loans
            $table->unsignedBigInteger('savings_id')->nullable();
            $table->unsignedBigInteger('shares_id')->nullable();
            $table->unsignedBigInteger('loan_forecast_id')->nullable();
            $table->timestamps();

            $table->foreign('savings_id')->references('id')->on('savings')->onDelete('set null');
            $table->foreign('shares_id')->references('id')->on('shares')->onDelete('set null');
            $table->foreign('loan_forecast_id')->references('id')->on('loan_forecast')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contra_acc');
    }
};
