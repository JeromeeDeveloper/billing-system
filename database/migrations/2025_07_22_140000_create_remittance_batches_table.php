<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('remittance_batches', function (Blueprint $table) {
            $table->id();
            $table->string('billing_period');
            $table->integer('remittance_tag');
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
            $table->unique(['billing_period', 'remittance_tag']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remittance_batches');
    }
};
