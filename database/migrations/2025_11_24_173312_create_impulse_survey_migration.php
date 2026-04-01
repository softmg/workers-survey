<?php

use App\Services\Survey\ImpulseTemplateService;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app()->make(ImpulseTemplateService::class)->createImpulseTemplate();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()->make(ImpulseTemplateService::class)->deleteImpulseTemplate();
    }
};
