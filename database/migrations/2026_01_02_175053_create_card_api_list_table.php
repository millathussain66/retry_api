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
        Schema::create('card_api_list', function (Blueprint $table) {
            $table->id();

            $table->string('api_name')->nullable();
            $table->string('api_url')->nullable();

            $table->enum('api_type', ['ETOB', 'NTOB'])
                ->comment('ETOB = EXISTING customer to Business, NTOB = NEW to Business');

            $table->string('api_code')->nullable();
            $table->integer('api_calling_sequence')->nullable();
            $table->boolean('is_active')
                ->default(true)
                ->comment('1 = Active, 0 = Inactive');
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_api_list');
    }
};
