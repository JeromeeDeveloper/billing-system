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
        Schema::create('remittance_previews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('emp_id')->nullable();
            $table->string('name')->nullable();
            $table->foreignId('member_id')->nullable()->constrained()->onDelete('cascade');
            $table->decimal('loans', 10, 2)->default(0);
            $table->json('savings')->nullable();
            $table->string('status');
            $table->string('message')->nullable();
            $table->string('type')->default('admin'); // 'admin' or 'branch'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remittance_previews');
    }
};
