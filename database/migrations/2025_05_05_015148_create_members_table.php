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
            $table->string('cid')->unique();
            $table->string('emp_id_no')->unique();
            $table->string('fname');
            $table->string('lname');
            $table->text('address');
            $table->decimal('savings_balance', 12, 2)->default(0);
            $table->decimal('share_balance', 12, 2)->default(0);
            $table->decimal('loan_balance', 12, 2)->default(0);
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
