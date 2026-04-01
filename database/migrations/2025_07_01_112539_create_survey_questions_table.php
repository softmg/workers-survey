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
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('survey_id');
            $table->unsignedBigInteger('answer_type_id');
            $table->string('question');
            $table->unsignedInteger('question_number')
                ->nullable()
                ->default(1)
                ->comment('Text questions text text text survey');

            $table->timestamps();

            $table->foreign('survey_id')
                ->references('id')
                ->on('surveys')
                ->onDelete('cascade');
            $table->foreign('answer_type_id')
                ->references('id')
                ->on('survey_answer_types')
                ->onDelete('cascade');

            $table->index(['survey_id', 'question_number']);
            $table->unique(['survey_id', 'question_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_questions');
    }
};
