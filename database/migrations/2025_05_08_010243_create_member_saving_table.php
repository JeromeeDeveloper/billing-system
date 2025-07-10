<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('member_saving', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->onDelete('cascade');
            $table->foreignId('saving_id')->constrained('savings')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['member_id', 'saving_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('member_saving');
    }
};
