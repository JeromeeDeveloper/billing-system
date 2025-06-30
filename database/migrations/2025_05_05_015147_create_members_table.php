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
        Schema::create('members', function (Blueprint $table) {
            $table->id();

            // Foreign key to branches
            $table->unsignedBigInteger('branch_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');

            $table->string('cid')->unique();
            $table->string('emp_id')->unique()->nullable();
            $table->string('fname')->nullable();
            $table->string('lname')->nullable();
            $table->text('address')->nullable();
            $table->decimal('savings_balance', 12, 2)->default(0);
            $table->decimal('share_balance', 12, 2)->default(0);
            $table->decimal('loan_balance', 12, 2)->default(0);
            $table->decimal('principal', 12, 2)->nullable()->default(0);
            $table->date('birth_date')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('date_registered')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('customer_type')->nullable();
            $table->string('customer_classification')->nullable();
            $table->string('occupation')->nullable();
            $table->string('industry')->nullable();
            $table->string('area_officer')->nullable();
            $table->string('area')->nullable();
            $table->string('account_name')->nullable();
            $table->enum('status', ['active', 'merged'])->default('active');
            $table->enum('member_tagging', ['PGB', 'New'])->nullable();

            $table->string('approval_no')->nullable();
            $table->date('start_hold')->nullable();
            $table->date('expiry_date')->nullable();
            $table->enum('account_status', ['deduction', 'non-deduction'])->default('deduction');

            $table->string('billing_period')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
