<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('loan_remittances', function (Blueprint $table) {
            $table->string('batch_id')->nullable()->index();
            $table->timestamp('imported_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('loan_remittances', function (Blueprint $table) {
            $table->dropColumn(['batch_id', 'imported_at']);
        });
    }
};
