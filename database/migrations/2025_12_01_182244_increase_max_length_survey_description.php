<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->string('description', 1023)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('surveys')
            ->whereRaw('CHAR_LENGTH(description) > 255')
            ->update([
                'description' => DB::raw('LEFT(description, 255)')
            ]);

        Schema::table('surveys', function (Blueprint $table) {
            $table->string('description', 255)->change();
        });
    }
};
