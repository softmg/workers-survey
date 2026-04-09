@php
    use App\Models\Survey\Survey;
    use App\Models\Worker\Worker;
    use Illuminate\Support\Carbon;
    /**
     * @var Survey $survey
     * @var Worker $worker
     */
@endphp

{{__('cron.notify_worker_survey_active.mattermost', [$worker->fio, Config::get('app.url') . '/survey/'. $survey->id, $survey->name])}}
@if ($survey->date_end)
    {{__('cron.notify_worker_survey_deadline_tail.default', [Carbon::parse($survey->date_end)->format('d-m-Y')])}}
@endif