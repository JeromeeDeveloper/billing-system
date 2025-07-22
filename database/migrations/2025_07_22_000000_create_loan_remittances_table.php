<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('loan_remittances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_forecast_id');
            $table->unsignedBigInteger('member_id');
            $table->decimal('remitted_amount', 12, 2);
            $table->decimal('applied_to_interest', 12, 2);
            $table->decimal('applied_to_principal', 12, 2);
            $table->decimal('remaining_interest_due', 12, 2);
            $table->decimal('remaining_principal_due', 12, 2);
            $table->decimal('remaining_total_due', 12, 2);
            $table->date('remittance_date');
            $table->timestamps();

            $table->foreign('loan_forecast_id')->references('id')->on('loan_forecast')->onDelete('cascade');
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_remittances');
    }
};
