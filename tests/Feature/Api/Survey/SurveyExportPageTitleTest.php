<?php

namespace Tests\Feature\Api\Survey;

use App\Exports\Sheets\SurveyResultsExportSheet;
use App\Exports\Sheets\SurveysResultsExportSheet;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyPage;
use Tests\Feature\FeatureTestCase;

class SurveyExportPageTitleTest extends FeatureTestCase
{
    public function test_surveys_results_export_sheet_formats_page_title_with_number(): void
    {
        $survey = Survey::factory()->create();
        SurveyPage::query()->create([
            'survey_id' => $survey->id,
            'number' => 3,
            'name' => 'Text text',
            'description' => null,
        ]);

        $survey->load('pages.questions');
        $sheet = new SurveysResultsExportSheet($survey, 'Sheet 1');

        $method = new \ReflectionMethod($sheet, 'formatPageName');
        $method->setAccessible(true);

        $this->assertSame('Text text 3', $method->invoke($sheet, 'Text text', 3));
        $this->assertSame('Text 4', $method->invoke($sheet, null, 4));
        $this->assertSame('Text 5', $method->invoke($sheet, '   ', 5));
    }

    public function test_survey_results_export_sheet_formats_page_title_with_number(): void
    {
        $survey = Survey::factory()->create();
        SurveyPage::query()->create([
            'survey_id' => $survey->id,
            'number' => 2,
            'name' => 'Text text',
            'description' => null,
        ]);

        $sheet = new SurveyResultsExportSheet(
            $survey,
            workers: null,
            departments: null,
            chiefs: null,
            completed: null,
        );

        $method = new \ReflectionMethod($sheet, 'formatPageName');
        $method->setAccessible(true);

        $this->assertSame('Text text 2', $method->invoke($sheet, 'Text text', 2));
        $this->assertSame('Text 1', $method->invoke($sheet, '', 1));
        $this->assertSame('Text 6', $method->invoke($sheet, null, 6));
    }
}
