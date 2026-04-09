<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $surveys = DB::table('surveys')->select('id')->get();
        $now = now();

        foreach ($surveys as $survey) {
            DB::table('survey_pages')->insert([
                'survey_id' => $survey->id,
                'number' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('survey_questions', function (Blueprint $table) {
            $table->unsignedBigInteger('survey_page_id')->nullable()->after('survey_id');
        });

        $surveyPages = DB::table('survey_pages')
            ->where('number', 1)
            ->pluck('id', 'survey_id');

        foreach ($surveyPages as $surveyId => $surveyPageId) {
            DB::table('survey_questions')
                ->where('survey_id', $surveyId)
                ->update(['survey_page_id' => $surveyPageId]);
        }

        Schema::table('survey_questions', function (Blueprint $table) {
            $table->unsignedBigInteger('survey_page_id')->nullable(false)->change();
            $table->foreign('survey_page_id')
                ->references('id')
                ->on('survey_pages')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('survey_questions', function (Blueprint $table) {
            $table->dropForeign(['survey_page_id']);
            $table->dropColumn('survey_page_id');
        });

        DB::table('survey_pages')
            ->delete();
    }
};
