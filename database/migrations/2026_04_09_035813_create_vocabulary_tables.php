<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('duolingo_id')->unique();
            $table->string('title');
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('section_number');
            $table->unsignedSmallInteger('unit_number');
            $table->timestamps();
        });

        // Deduplicated Han characters — stroke data lives here once per character
        // rather than duplicated on every word_token that references it.
        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->string('character')->unique();
            $table->json('strokes')->nullable();
            $table->timestamps();
        });

        Schema::create('words', function (Blueprint $table) {
            $table->id();
            $table->string('text')->unique();
            $table->string('translation')->nullable();
            $table->string('pinyin');
            $table->string('tts_url')->nullable();
            $table->timestamps();
        });

        // Per-character breakdown of each word, ordered by position.
        // Query word_tokens via character_id to find all words containing a given character.
        Schema::create('word_characters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('word_id')->constrained()->cascadeOnDelete();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->string('pinyin');
            $table->unsignedSmallInteger('position');
            $table->timestamps();

            $table->unique(['word_id', 'position']);
        });

        Schema::create('section_word', function (Blueprint $table) {
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('word_id')->constrained()->cascadeOnDelete();
            $table->primary(['section_id', 'word_id']);
        });

        Schema::create('user_word', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('word_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_available')->default(false);
            $table->timestamps();

            $table->primary(['user_id', 'word_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_word');
        Schema::dropIfExists('section_word');
        Schema::dropIfExists('word_characters');
        Schema::dropIfExists('words');
        Schema::dropIfExists('characters');
        Schema::dropIfExists('sections');
    }
};
