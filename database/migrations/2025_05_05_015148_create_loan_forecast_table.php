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
        Schema::create('loan_forecast', function (Blueprint $table) {
            $table->id();
            $table->string('loan_acct_no')->unique();
            $table->decimal('amount_due', 12, 2)->default(0);
            $table->date('open_date');
            $table->date('maturity_date');
            $table->date('amortization_due_date');
            $table->decimal('total_due', 12, 2)->default(0);
            $table->decimal('principal_due', 12, 2)->default(0);
            $table->decimal('interest_due', 12, 2)->default(0);
            $table->decimal('principal', 12, 2)->nullable()->default(0);
            $table->decimal('penalty_due', 12, 2)->default(0);
            $table->unsignedBigInteger('member_id');
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->string('billing_period')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_forecast');
    }
};
