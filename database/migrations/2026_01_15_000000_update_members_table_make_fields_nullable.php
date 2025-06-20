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
        Schema::table('members', function (Blueprint $table) {
            // Make fname and lname nullable
            $table->string('fname')->nullable()->change();
            $table->string('lname')->nullable()->change();

            // Make branch_id nullable
            $table->unsignedBigInteger('branch_id')->nullable()->change();

            // Drop the foreign key constraint first, then recreate it
            $table->dropForeign(['branch_id']);
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // Revert fname and lname to not nullable
            $table->string('fname')->nullable(false)->change();
            $table->string('lname')->nullable(false)->change();

            // Revert branch_id to not nullable
            $table->unsignedBigInteger('branch_id')->nullable(false)->change();

            // Drop and recreate foreign key constraint
            $table->dropForeign(['branch_id']);
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
        });
    }
};
