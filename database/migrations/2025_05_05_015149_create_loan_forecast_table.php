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
            $table->string('acct_no');
            $table->decimal('amount_due', 12, 2)->default(0);
            $table->date('due_date');
            $table->decimal('principal_due', 12, 2)->default(0);
            $table->decimal('interest_due', 12, 2)->default(0);
            $table->decimal('penalty', 12, 2)->default(0);
            $table->unsignedBigInteger('member_id');  // Change to unsignedBigInteger
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
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
