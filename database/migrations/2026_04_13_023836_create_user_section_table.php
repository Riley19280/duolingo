<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_section', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_unlocked')->default(false);
            $table->timestamps();

            $table->primary(['user_id', 'section_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_section');
    }
};
