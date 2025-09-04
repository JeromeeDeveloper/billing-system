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
        Schema::table('export_statuses', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('export_type');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Drop the old unique constraint
            $table->dropUnique(['billing_period', 'export_type']);

            // Add new unique constraint including user_id
            $table->unique(['billing_period', 'export_type', 'user_id'], 'export_statuses_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('export_statuses', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');

            // Restore the old unique constraint
            $table->unique(['billing_period', 'export_type']);
        });
    }
};
