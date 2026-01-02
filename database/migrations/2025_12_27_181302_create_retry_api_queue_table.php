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
        Schema::create('retry_api_queue', function (Blueprint $table) {
            $table->id();
            $table->integer('api_id');
            $table->string('api_name')->nullable();
            $table->integer('lead_id');
            $table->integer('file_info_id');
            $table->integer('candidate_info_id');
            $table->string('file_sl_no')->nullable();
            $table->json('payload')->nullable();
            $table->json('response')->nullable();
            $table->boolean('success_status')->default(0);
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retry_api_queue');
    }
};
