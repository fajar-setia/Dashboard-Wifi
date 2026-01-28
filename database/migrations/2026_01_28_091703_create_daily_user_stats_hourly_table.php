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
        Schema::create('daily_user_stats_hourly', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->unsignedTinyInteger('hour')->comment('0-23');
            $table->unsignedInteger('user_count')->default(0);
            $table->timestamps();
            
            $table->unique(['date', 'hour']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_user_stats_hourly');
    }
};
