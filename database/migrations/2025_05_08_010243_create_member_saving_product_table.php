<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('member_saving_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->onDelete('cascade');
            $table->foreignId('saving_product_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Ensure a member can't have the same saving product multiple times
            $table->unique(['member_id', 'saving_product_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('member_saving_product');
    }
};
