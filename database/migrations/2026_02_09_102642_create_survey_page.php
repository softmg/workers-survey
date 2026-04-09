<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('survey_pages', function (Blueprint $table) {
            $table->id();
            $table->integer('number');
            $table->char('name', 255)->nullable();
            $table->char('description', 255)->nullable();
            $table->foreignId('survey_id')->constrained('surveys');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_pages');
    }
};
