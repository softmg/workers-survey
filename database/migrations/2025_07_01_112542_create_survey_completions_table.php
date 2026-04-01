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
        Schema::create('survey_completions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('worker_id');
            $table->unsignedBigInteger('survey_id');
            $table->boolean('completed')->default(false);
            $table->date('completion_date')->nullable();
            $table->timestamps();

            $table->foreign('worker_id')
                ->references('id')
                ->on('workers')
                ->onDelete('cascade');
            $table->foreign('survey_id')
                ->references('id')
                ->on('surveys')
                ->onDelete('cascade');

            $table->unique(['worker_id', 'survey_id'], 'uniq_worker_survey');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_completions');
    }
};
