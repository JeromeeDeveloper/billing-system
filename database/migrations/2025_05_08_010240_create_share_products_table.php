<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('share_products', function (Blueprint $table) {
            $table->id();
            $table->string('product_name');
            $table->string('product_code')->unique();
            $table->decimal('amount_to_deduct', 12, 2)->nullable();

            $table->timestamps();
        });

    }

    public function down()
    {

        Schema::dropIfExists('share_products');
    }
};
