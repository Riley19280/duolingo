<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Course sections, e.g. "Section 2, Unit 3"
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('duolingo_id')->unique();    // Duolingo's own identifier ("0", "12", …)
            $table->string('title');                    // Human-readable label, e.g. "Section 2, Unit 3"
            $table->string('description')->nullable();                    // Human-readable label, e.g. "Section 2, Unit 3"
            $table->unsignedSmallInteger('section_number'); // Parsed from title
            $table->unsignedSmallInteger('unit_number');    // Parsed from title
            $table->timestamps();
        });

        // Deduplicated vocabulary entries
        Schema::create('words', function (Blueprint $table) {
            $table->id();
            $table->string('text')->unique();      // Chinese word/character(s), e.g. "水果"
            $table->string('translation')->nullable(); // English gloss, e.g. "fruit"
            $table->string('pinyin');               // Full romanisation, e.g. "shuǐguǒ"
            $table->string('tts_url')->nullable();  // CloudFront audio URL
            $table->string('state')->default('LOCKED'); // AVAILABLE | LOCKED
            $table->timestamps();
        });

        // Per-character pinyin breakdown for each word.
        // Replaces the characterIndex from stats.json — query this table to find
        // all words containing a given character instead of rebuilding it in PHP.
        Schema::create('word_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('word_id')->constrained()->cascadeOnDelete();
            $table->string('character');               // Single Han character, e.g. "水"
            $table->string('pinyin');                  // Tone-marked pinyin for this character in context
            $table->integer('position');  // 0-indexed position within the word
            $table->timestamps();

            $table->index('character');               // Fast character → words lookups
            $table->unique(['word_id', 'position']);  // Enforce ordering integrity
        });

        // Many-to-many: a word can appear across multiple sections,
        // and a section contains many words.
        Schema::create('section_word', function (Blueprint $table) {
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('word_id')->constrained()->cascadeOnDelete();
            $table->primary(['section_id', 'word_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_word');
        Schema::dropIfExists('word_tokens');
        Schema::dropIfExists('words');
        Schema::dropIfExists('sections');
    }
};
