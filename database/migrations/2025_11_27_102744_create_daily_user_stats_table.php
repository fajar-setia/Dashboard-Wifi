<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDailyUserStatsTable extends Migration
{
    public function up()
    {
        Schema::create('daily_user_stats', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->integer('user_count')->default(0);
            $table->json('meta')->nullable(); // optional: simpan detail (mis. list mac) jika perlu
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('daily_user_stats');
    }
}

