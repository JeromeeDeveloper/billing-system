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
        Schema::create('remittance_reports', function (Blueprint $table) {
            $table->id();
            $table->string('cid');
            $table->string('member_name');
            $table->decimal('remitted_loans', 15, 2)->default(0);
            $table->decimal('remitted_savings', 15, 2)->default(0);
            $table->decimal('remitted_shares', 15, 2)->default(0);
            $table->string('period')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remittance_reports');
    }
};
