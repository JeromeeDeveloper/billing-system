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
        Schema::create('remittance_file', function (Blueprint $table) {
            $table->id();
            $table->string('emp_id_no')->nullable(); // Might be missing
            $table->decimal('loan_payment', 12, 2)->default(0);
            $table->decimal('savings_dep', 12, 2)->default(0);
            $table->decimal('share_dep', 12, 2)->default(0);
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
        Schema::dropIfExists('remittance_file');
    }
};
