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
        // Add the missing document types to the ENUM
        DB::statement("ALTER TABLE document_uploads MODIFY COLUMN document_type ENUM('Installment File', 'CIF', 'Savings', 'Shares', 'Loan', 'CoreID', 'Savings & Shares Product')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the added document types from the ENUM
        DB::statement("ALTER TABLE document_uploads MODIFY COLUMN document_type ENUM('Installment File', 'CIF', 'Savings', 'Shares', 'Loan')");
    }
};
