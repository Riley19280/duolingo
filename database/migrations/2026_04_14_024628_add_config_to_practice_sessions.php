<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('practice_sessions', function (Blueprint $table) {
            $table->string('exercise_type')->after('practice_set_id');
            $table->string('question_form')->after('exercise_type');
            $table->string('answer_form')->after('question_form');
        });
    }

    public function down(): void
    {
        Schema::table('practice_sessions', function (Blueprint $table) {
            $table->dropColumn(['exercise_type', 'question_form', 'answer_form']);
        });
    }
};
