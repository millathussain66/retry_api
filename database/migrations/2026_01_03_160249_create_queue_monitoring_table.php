<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQueueMonitoringTable extends Migration
{
    public function up()
    {
        Schema::create('queue_monitoring', function (Blueprint $table) {
            $table->id();
            $table->string('queue');
            $table->integer('size');
            $table->string('status');
            $table->json('metrics')->nullable();
            $table->timestamp('monitored_at');
            $table->timestamps();
            
            $table->index(['queue', 'monitored_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('queue_monitoring');
    }
}