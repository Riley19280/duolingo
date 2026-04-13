<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // User-defined named collections of words to practice.
        Schema::create('practice_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        // Words belonging to a practice set.
        Schema::create('practice_set_word', function (Blueprint $table) {
            $table->foreignId('practice_set_id')->constrained()->cascadeOnDelete();
            $table->foreignId('word_id')->constrained()->cascadeOnDelete();
            $table->primary(['practice_set_id', 'word_id']);
        });

        // A single sitting of practice against a set.
        // practice_set_id is nullable so sessions survive set deletion.
        Schema::create('practice_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practice_set_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        // One row per exercise shown to the user during a session.
        // Supports: typing, matching, dictation, pinyin, multiple_choice.
        Schema::create('practice_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('word_id')->constrained()->cascadeOnDelete();
            $table->string('exercise_type');
            $table->text('given_answer')->nullable();  // what the user submitted
            $table->text('correct_answer');            // expected answer at time of attempt
            $table->boolean('is_correct');
            $table->unsignedInteger('response_time_ms')->nullable(); // time from prompt to submission
            $table->timestamps();

            $table->index(['word_id', 'exercise_type']);     // accuracy per word per type
            $table->index(['practice_session_id', 'is_correct']); // session scoring
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practice_attempts');
        Schema::dropIfExists('practice_sessions');
        Schema::dropIfExists('practice_set_word');
        Schema::dropIfExists('practice_sets');
    }
};
