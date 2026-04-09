<?php

namespace Database\Seeders;

use App\Enums\SurveyAnswerTypeEnum;
use App\Models\Survey\SurveyAnswer;
use App\Models\Survey\SurveyAnswerVariants;
use App\Models\Survey\SurveyCompletion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SurveyAnswerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Text text text text text text text
        SurveyCompletion::with([
            'worker',
            'survey.questions.variants',
            'survey.questions.answerType',
        ])
            ->where('completed', true)
            ->get()
            ->each(function ($completion) {
                $worker = $completion->worker;

                DB::transaction(function () use ($completion, $worker) {
                    foreach ($completion->survey->questions as $question) {

                        $answerTypeModel = $question->answerType;
                        $baseTypeEnum = $answerTypeModel->base_type;

                        $data = [
                            'worker_id'   => $worker->id,
                            'question_id' => $question->id,
                            'answer_text' => null,
                            'answer_int'  => null,
                        ];
                        $variants = [];

                        $handler = match ($baseTypeEnum) {
                            SurveyAnswerTypeEnum::Radio,
                            SurveyAnswerTypeEnum::Checkbox => function () use (
                                $question,
                                $answerTypeModel,
                                &$data,
                                &$variants
                            ) {
                                $first = $question->variants->random();
                                $variants[] = ['id' => $first->id];

                                if ($answerTypeModel->multiple && $question->variants->count() > 1) {
                                    $second = $question->variants
                                        ->where('id', '!=', $first->id)
                                        ->random();
                                    $variants[] = ['id' => $second->id];
                                }

                                if ($answerTypeModel->custom) {
                                    $data['answer_text'] = 'Text variant answer';
                                }
                            },

                            SurveyAnswerTypeEnum::Text => function () use (&$data) {
                                $data['answer_text'] = 'Text answer';
                            },

                            SurveyAnswerTypeEnum::Integer => function () use (
                                $answerTypeModel,
                                &$data
                            ) {
                                $data['answer_int'] = $answerTypeModel->limited
                                    ? rand($answerTypeModel->min, $answerTypeModel->max)
                                    : rand(1, 100);
                            },
                        };

                        $handler();

                        $answer = SurveyAnswer::updateOrCreate(
                            [
                                'worker_id'   => $worker->id,
                                'question_id' => $question->id,
                            ],
                            $data
                        );

                        foreach ($variants as $v) {
                            SurveyAnswerVariants::firstOrCreate(
                                [
                                    'survey_answer_id' => $answer->id,
                                    'variant_id'       => $v['id'],
                                ]
                            );
                        }
                    }
                });
            });
    }
}
