<?php

namespace App\Console\Commands\Survey;

use App\Services\Survey\ImpulseService;
use Illuminate\Console\Command;

class CreateImpulseSurveyCommand extends Command
{
    public function __construct(
        private readonly ImpulseService $pulseService,
    ) {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:create-new-pulse-survey {monthSub=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Text text survey "Text text \'text\'" ';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $monthSub = $this->argument('monthSub') ?? 1;

        if (!is_numeric($monthSub)) {
            $this->warn('Text invalid argument');
            return;
        }

        $this->pulseService->createImpulseSurvey($monthSub);
    }
}
