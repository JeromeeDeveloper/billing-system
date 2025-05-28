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
        Schema::create('document_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branches_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->foreignId('member_id')->nullable()->constrained('members')->onDelete('cascade');
            $table->foreignId('collection_id')->nullable()->constrained('collection')->onDelete('cascade');
            $table->foreignId('bill_id')->nullable()->constrained('bill')->onDelete('cascade');
            $table->foreignId('remittance_id')->nullable()->constrained('remittance')->onDelete('cascade');
            $table->foreignId('loan_forecast_id')->nullable()->constrained('loan_forecast')->onDelete('cascade');
            $table->foreignId('atm_module_id')->nullable()->constrained('atm_module')->onDelete('cascade');
            $table->foreignId('master_list_id')->nullable()->constrained('master_list')->onDelete('cascade');
            $table->enum('document_type', [
                'Installment File',
                'CIF',
                'Savings',
                'Shares'
            ]);
            $table->string('filename');
            $table->string('filepath');
            $table->string('mime_type')->nullable();
            $table->string('billing_period')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamp('upload_date')->useCurrent();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_uploads');
    }
};
