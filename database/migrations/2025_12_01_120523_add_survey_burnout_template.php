<?php

use App\Enums\SurveyAnswerTypeEnum;
use App\Enums\SurveyStatusEnum;
use App\Models\Survey\SurveyType;
use App\Support\Surveys\Templates\ProfessionalBurnoutQuestionsTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('survey_questions', function (Blueprint $table) {
            $table->string('code')->nullable()->index();
        });

        $surveyType = SurveyType::query()
            ->where('code', 'professional burnout')
            ->first();

        if (!$surveyType) {
            throw new RuntimeException("SurveyType text text code 'professional burnout' text text");
        }

        $surveyTemplateId = DB::table('surveys')->insertGetId([
            'name' => 'Template survey Text',
            'description' => 'Template survey Text text text text text text',
            'survey_type_id' => $surveyType->id,
            'status' => SurveyStatusEnum::Template->value,
            'date_end' => now()->toDateString(),
            'is_template' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $surveyAnswerTypeId = DB::table('survey_answer_types')->insertGetId([
            'title' => 'Text text, text 0-6',
            'base_type' => SurveyAnswerTypeEnum::Integer->value,
            'custom' => true,
            'multiple' => false,
            'limited' => true,
            'min' => 0,
            'max' => 6,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $questions = $this->surveyQuestions($surveyTemplateId, $surveyAnswerTypeId);
        DB::table('survey_questions')->insert($questions);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $surveyIds = DB::table('surveys')
            ->where('name', 'Template survey Text')
            ->where('is_template', true)
            ->pluck('id');

        if ($surveyIds->isEmpty()) {
            return;
        }

        $surveyIdsArray = $surveyIds->all();

        DB::table('surveys')
            ->whereIn('id', $surveyIdsArray)
            ->delete();

        DB::table('survey_answer_types')
            ->where('title', 'Text text, text 0-6')
            ->delete();

        Schema::table('survey_questions', function (Blueprint $table) {
            $table->dropIndex(['code']);
            $table->dropColumn('code');
        });

        // Questions text text
    }

    private function surveyQuestions(int $surveyId, int $surveyAnswerTypeId): array
    {
        $now = now();
        $questions = ProfessionalBurnoutQuestionsTemplate::questions();

        $i = 0;
        foreach ($questions as $code => $row) {
            $i++;
            $questions[$code] = [
                'question_number' => $i,
                'survey_id' => $surveyId,
                'question' => $row['title'],
                'answer_type_id' => $surveyAnswerTypeId,
                'created_at' => $now,
                'updated_at' => $now,
                'code' => $code,
            ];
        }

        return $questions;
    }
};
