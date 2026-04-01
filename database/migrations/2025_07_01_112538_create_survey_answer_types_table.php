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
        Schema::create('survey_answer_types', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique()->comment('Name type answer');
            $table->string('base_type')->comment('Text type');
            $table->boolean('custom')->default(false)->comment('Text text text answer');
            $table->boolean('multiple')->default(false)->comment('Text text text variants');
            $table->boolean('limited')->default(false)->nullable()->comment('Text text text answer');
            $table->integer('min')->nullable();
            $table->integer('max')->nullable();
            $table->timestamps();
        });

        DB::table('survey_answer_types')->insert([
            [
                'title' => 'Text text',
                'base_type' => 'radio',
                'custom' => false,
                'multiple' => false,
                'limited' => false,
                'min' => null,
                'max' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Text text',
                'base_type' => 'checkbox',
                'custom' => false,
                'multiple' => true,
                'limited' => false,
                'min' => null,
                'max' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Text question',
                'base_type' => 'text',
                'custom' => true,
                'multiple' => false,
                'limited' => false,
                'min' => null,
                'max' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Text text',
                'base_type' => 'integer',
                'custom' => false,
                'multiple' => false,
                'limited' => false,
                'min' => null,
                'max' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Text text, text 1-5',
                'base_type' => 'integer',
                'custom' => false,
                'multiple' => false,
                'limited' => true,
                'min' => 1,
                'max' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Text text, text 1-10',
                'base_type' => 'integer',
                'custom' => false,
                'multiple' => false,
                'limited' => true,
                'min' => 1,
                'max' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Text text, text answer',
                'base_type' => 'radio',
                'custom' => true,
                'multiple' => false,
                'limited' => false,
                'min' => null,
                'max' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Text text, text answer',
                'base_type' => 'checkbox',
                'custom' => true,
                'multiple' => true,
                'limited' => false,
                'min' => null,
                'max' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_answer_types');
    }
};
