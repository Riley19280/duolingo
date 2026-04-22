<?php

use App\Http\Controllers\CharacterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\PracticeController;
use App\Http\Controllers\PracticeSessionController;
use App\Http\Controllers\PracticeSetController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\WordController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');
// Route::inertia('/', 'welcome', [
//    'canRegister' => Features::enabled(Features::registration()),
// ])->name('home');

Route::get('tts/{filename}', function (string $filename) {
    $path = public_path('tts/'.urldecode($filename));

    abort_if(! file_exists($path), 404);

    return response()->file($path, ['Content-Type' => 'audio/mpeg']);
})->where('filename', '.+\.mp3')->name('tts');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('characters/{character}', [CharacterController::class, 'show'])->name('characters.show');
    Route::resource('practice', PracticeController::class)->only(['index']);
    Route::resource('practice/sessions', PracticeSessionController::class)
        ->only(['store', 'show'])
        ->names('practice.sessions')
        ->parameters(['sessions' => 'practiceSession']);
    Route::post('practice/sessions/{practiceSession}/complete', [PracticeSessionController::class, 'complete'])
        ->name('practice.sessions.complete')
        ->middleware('auth');
    Route::resource('practice/sets', PracticeSetController::class)
        ->only(['create', 'store', 'edit', 'update', 'destroy'])
        ->names('practice.sets')
        ->parameters(['sets' => 'practiceSet']);

    Route::resource('sections', SectionController::class)
        ->only(['index', 'show', 'update']);

    Route::get('words', [WordController::class, 'index'])->name('words.index');
    Route::resource('words.notes', NoteController::class)
        ->only(['store', 'update'])
        ->shallow();
    Route::delete('notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');
});

require __DIR__.'/settings.php';
