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
        Schema::create('collection', function (Blueprint $table) {
            $table->id();
            $table->string('sv_acct_no');
            $table->decimal('sv_dep_amount', 12, 2)->default(0);
            $table->string('atm_sv_acct_no');
            $table->decimal('atm_sv_dep', 12, 2)->default(0);
            $table->string('sc_acct_no');
            $table->decimal('sc_dep_amount', 12, 2)->default(0);
            $table->string('ln_acct_no');
            $table->decimal('ln_payment', 12, 2)->default(0);
            $table->unsignedBigInteger('member_id');  
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection');
    }
};
