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
        Schema::table('loan_payments', function (Blueprint $table) {
            $table->decimal('applied_to_interest', 12, 2)->default(0)->after('amount');
            $table->decimal('applied_to_principal', 12, 2)->default(0)->after('applied_to_interest');
            $table->decimal('penalty', 12, 2)->default(0)->after('applied_to_principal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_payments', function (Blueprint $table) {
            $table->dropColumn(['applied_to_interest', 'applied_to_principal', 'penalty']);
        });
    }
};
