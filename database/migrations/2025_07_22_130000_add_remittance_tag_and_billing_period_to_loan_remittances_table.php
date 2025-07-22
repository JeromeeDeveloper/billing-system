<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('loan_remittances', function (Blueprint $table) {
            $table->integer('remittance_tag')->nullable()->index();
            $table->string('billing_period')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('loan_remittances', function (Blueprint $table) {
            $table->dropColumn(['remittance_tag', 'billing_period']);
        });
    }
};
