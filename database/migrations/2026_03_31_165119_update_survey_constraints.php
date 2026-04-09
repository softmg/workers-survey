<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE survey_pages MODIFY COLUMN description VARCHAR(500) NULL');
        DB::statement('ALTER TABLE survey_question_variants MODIFY COLUMN variant VARCHAR(500) NOT NULL');
        DB::statement('ALTER TABLE survey_questions MODIFY COLUMN question VARCHAR(1000) NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE survey_pages MODIFY COLUMN description VARCHAR(255) NULL');
        DB::statement('ALTER TABLE survey_question_variants MODIFY COLUMN variant VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE survey_questions MODIFY COLUMN question VARCHAR(255) NOT NULL');
    }
};
