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
        Schema::create('bill', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('member_id');
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->foreignId('loan_forecast_id')->nullable()->constrained('loan_forecast')->onDelete('cascade');
            $table->foreignId('branches_id')->nullable()->constrained('branches')->onDelete('cascade');

            $table->string('amortization');
            $table->date('start');
            $table->date('end');
            $table->decimal('gross', 12, 2)->default(0);

            $table->enum('status', ['pending', 'approved'])->nullable()->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill');
    }
};
