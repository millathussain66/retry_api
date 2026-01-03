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
        Schema::create('candidate_info', function (Blueprint $table) {
            $table->id();
            $table->integer('lead_id');
            $table->integer('file_info_id');
            $table->string('file_sl_no');
            $table->enum('candidate_type', ['Applicant', 'Supplementary'])->comment('applicant = Main Applicant, supplementary = Supplementary Candidate');
            $table->string('candidate_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_info');
    }
};
