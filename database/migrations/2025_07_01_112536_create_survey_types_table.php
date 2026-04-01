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
        Schema::create('survey_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name')->unique();
            $table->timestamps();
        });

        DB::table('survey_types')->insert([
            ['code' => 'default', 'name' => 'Text'],
            ['code' => 'onboarding', 'name' => 'Text'],
            ['code' => 'offboarding', 'name' => 'Text'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_types');
    }
};
