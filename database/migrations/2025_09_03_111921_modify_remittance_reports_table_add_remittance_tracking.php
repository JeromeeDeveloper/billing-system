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
        Schema::table('remittance_reports', function (Blueprint $table) {
            // Add remittance upload tracking
            $table->integer('remittance_tag')->after('period')->comment('Which remittance upload this record belongs to (1, 2, 3, etc.)');
            $table->enum('remittance_type', ['loans_savings', 'shares'])->after('remittance_tag')->comment('Type of remittance: loans_savings or shares');
            $table->decimal('billed_amount', 15, 2)->default(0)->after('remitted_shares')->comment('Total billed amount for this remittance type');

            // Add unique constraint to prevent duplicate records for same member, period, tag, and type
            $table->unique(['cid', 'period', 'remittance_tag', 'remittance_type'], 'unique_remittance_report');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('remittance_reports', function (Blueprint $table) {
            $table->dropUnique('unique_remittance_report');
            $table->dropColumn(['remittance_tag', 'remittance_type', 'billed_amount']);
        });
    }
};
