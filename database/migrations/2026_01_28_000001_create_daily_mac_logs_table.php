<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDailyMacLogsTable extends Migration
{
    public function up()
    {
        Schema::create('daily_mac_logs', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->string('mac_address', 17)->index(); // Format: XX:XX:XX:XX:XX:XX
            $table->string('location', 255)->nullable();
            $table->string('kemantren', 255)->nullable();
            $table->timestamp('first_seen')->useCurrent();
            $table->timestamp('last_seen')->useCurrent()->useCurrentOnUpdate();
            $table->timestamps();

            // Unique constraint per hari per MAC
            $table->unique(['date', 'mac_address']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('daily_mac_logs');
    }
}
