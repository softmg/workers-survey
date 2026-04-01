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
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedBigInteger('survey_type_id')->nullable();
            $table->string('status')->default('edit')->comment('SurveyStatusEnum');
            $table->date('date_end')->nullable();
            $table->timestamps();

            $table->foreign('survey_type_id')
                ->references('id')
                ->on('survey_types')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};
