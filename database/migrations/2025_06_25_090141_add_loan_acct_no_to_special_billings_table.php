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
        Schema::table('special_billings', function (Blueprint $table) {
            $table->string('loan_acct_no')->nullable()->after('cid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('special_billings', function (Blueprint $table) {
            $table->dropColumn('loan_acct_no');
        });
    }
};
