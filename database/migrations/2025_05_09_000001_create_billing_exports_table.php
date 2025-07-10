<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_exports', function (Blueprint $table) {
            $table->id();
            $table->string('billing_period');
            $table->string('filename');
            $table->string('filepath');
            $table->unsignedBigInteger('generated_by');
            $table->foreign('generated_by')->references('id')->on('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_exports');
    }
};
