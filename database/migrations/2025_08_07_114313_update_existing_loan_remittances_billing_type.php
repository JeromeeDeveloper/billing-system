<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing LoanRemittance records to have billing_type based on their batch
        // First, get all LoanRemittance records that have NULL billing_type
        $loanRemittances = DB::table('loan_remittances')
            ->whereNull('billing_type')
            ->get();

        foreach ($loanRemittances as $loanRemittance) {
            // Find the corresponding RemittanceBatch record
            $batch = DB::table('remittance_batches')
                ->where('remittance_tag', $loanRemittance->remittance_tag)
                ->where('billing_period', $loanRemittance->billing_period)
                ->first();

            if ($batch) {
                // Update the LoanRemittance record with the billing_type from the batch
                DB::table('loan_remittances')
                    ->where('id', $loanRemittance->id)
                    ->update(['billing_type' => $batch->billing_type]);
            } else {
                // If no batch found, default to 'regular' for backward compatibility
                DB::table('loan_remittances')
                    ->where('id', $loanRemittance->id)
                    ->update(['billing_type' => 'regular']);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse this migration as it's just updating existing data
    }
};
