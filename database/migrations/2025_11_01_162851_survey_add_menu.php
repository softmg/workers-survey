<?php

use App\Utils\AdminMenuAdder;
use Encore\Admin\Auth\Database\Menu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    use AdminMenuAdder;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->string('status')
                ->default('created')
                ->comment('SurveyStatusEnum')
                ->change();
        });

        $surveysMenu = $this->addMenuGroupIfNotExists('Surveys', 'fa-clipboard');
        $this->addMenuIfNotExists('surveys', 'fa-bars', $surveysMenu->id);
        $this->addMenuIfNotExists('survey_answer_types', 'fa-bars', $surveysMenu->id);
        $this->addMenuIfNotExists('survey_types', 'fa-bars', $surveysMenu->id);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Menu::query()->where('uri', 'surveys')->delete();
        Menu::query()->where('uri', 'survey_answer_types')->delete();
        Menu::query()->where('uri', 'survey_types')->delete();

        Menu::query()->where('title', 'Surveys')->delete();
    }
};
