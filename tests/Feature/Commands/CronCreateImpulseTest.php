<?php

namespace Feature\Commands;

use Tests\Feature\FeatureTestCase;

class CronCreateImpulseTest extends FeatureTestCase
{
    public function test_it_can_create_impulse(): void
    {
        $this->artisan('cron:create-new-pulse-survey')->assertSuccessful();
    }
}
