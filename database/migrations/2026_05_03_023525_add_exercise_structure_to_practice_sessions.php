<?php

use App\Models\PracticeSession;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('practice_sessions', function (Blueprint $table) {
            $table->string('exercise_structure')->nullable()->after('updated_at');
        });

        PracticeSession::query()->update(['exercise_structure' => 'word']);

        Schema::table('practice_sessions', function (Blueprint $table) {
            $table->string('exercise_structure')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('practice_sessions', function (Blueprint $table) {
            $table->dropColumn('exercise_structure');
        });
    }
};
