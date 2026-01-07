<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('daily_location_stats', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('location')->index();
            $table->string('kemantren')->nullable();
            $table->string('sn')->nullable();
            $table->integer('user_count')->default(0);
            $table->timestamps();
            $table->unique(['date', 'location', 'sn']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('daily_location_stats');
    }
};
