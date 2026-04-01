<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('survey_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('worker_id');
            $table->unsignedBigInteger('question_id');
            $table->text('answer_text')->nullable();
            $table->integer('answer_int')->nullable();
            $table->timestamps();

            $table->foreign('worker_id')->references('id')->on('workers')->onDelete('cascade');
            $table->foreign('question_id')->references('id')->on('survey_questions')->onDelete('cascade');
        });

        Schema::create('survey_answer_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('survey_answer_id');
            $table->unsignedBigInteger('variant_id');
            $table->timestamps();

            $table->foreign('survey_answer_id')
                ->references('id')->on('survey_answers')
                ->onDelete('cascade');

            $table->foreign('variant_id')
                ->references('id')->on('survey_question_variants')
                ->onDelete('cascade');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('survey_answer_variants');
        Schema::dropIfExists('survey_answers');
    }
};
