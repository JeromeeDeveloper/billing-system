<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('savings_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('savings_id');
            $table->unsignedBigInteger('atm_payment_id');
            $table->string('account_number');
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->string('reference_number')->nullable();
            $table->timestamps();

            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->foreign('savings_id')->references('id')->on('savings')->onDelete('cascade');
            $table->foreign('atm_payment_id')->references('id')->on('atm_payments')->onDelete('cascade');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('savings_payments');
    }
};
