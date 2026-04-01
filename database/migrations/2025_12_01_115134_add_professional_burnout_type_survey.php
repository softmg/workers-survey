<?php

use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('survey_types')->insert([
            'name' => 'Text',
            'code' => 'professional burnout',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('survey_types')
            ->where('code', 'professional burnout')
            ->delete();
    }
};
