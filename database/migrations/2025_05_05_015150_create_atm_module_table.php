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
        Schema::create('atm_module', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount_swipe', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->string('atm_sv_account');
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
        Schema::dropIfExists('atm_module');
    }
};
