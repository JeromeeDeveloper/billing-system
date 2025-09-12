<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Expand enum to include admin-msp
        DB::statement("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('admin','branch','admin-msp') NOT NULL DEFAULT 'admin'");
    }

    public function down(): void
    {
        // Revert to original enum
        DB::statement("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('admin','branch') NOT NULL DEFAULT 'admin'");
    }
};


