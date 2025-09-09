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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('billing_approval_status', ['pending', 'approved'])->default('pending')->after('status');
            $table->enum('special_billing_approval_status', ['pending', 'approved'])->default('pending')->after('billing_approval_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['billing_approval_status', 'special_billing_approval_status']);
        });
    }
};
