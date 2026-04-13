<?php

use App\Http\Controllers\CharacterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SectionController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('characters/{character}', [CharacterController::class, 'show'])->name('characters.show');
    Route::get('sections', [SectionController::class, 'index'])->name('sections.index');
    Route::get('sections/{section}', [SectionController::class, 'show'])->name('sections.show');
    Route::put('sections/{section}', [SectionController::class, 'update'])->name('sections.update');
});

require __DIR__.'/settings.php';
