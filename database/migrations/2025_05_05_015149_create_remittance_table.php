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
        Schema::create('remittance', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('member_id');  // Change to unsignedBigInteger
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');

            $table->unsignedBigInteger('branch_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');

            $table->decimal('loan_payment', 12, 2)->default(0);
            $table->decimal('savings_dep', 12, 2)->default(0);
            $table->decimal('share_dep', 12, 2)->default(0);

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remittance');
    }
};
