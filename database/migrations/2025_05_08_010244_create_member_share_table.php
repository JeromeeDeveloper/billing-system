<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('member_share', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->onDelete('cascade');
            $table->foreignId('share_id')->constrained('shares')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['member_id', 'share_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('member_share');
    }
};
