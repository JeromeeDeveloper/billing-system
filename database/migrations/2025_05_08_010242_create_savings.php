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
        Schema::create('savings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->nullable()->constrained('members')->onDelete('cascade');
            $table->string('account_number')->unique();
            $table->string('product_code')->nullable();
            $table->string('product_name')->nullable();
            $table->date('open_date');
            $table->decimal('current_balance', 15, 2)->nullable();
            $table->decimal('available_balance', 15, 2)->nullable();
            $table->decimal('interest', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('savings');
    }
};
