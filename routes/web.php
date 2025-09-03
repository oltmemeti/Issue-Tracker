<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserStoryController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\IssueCommentController;
use App\Http\Controllers\IssueController;


Route::post('/stories', [UserStoryController::class, 'store'])->name('stories.store');


Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
Route::get('/', [TaskController::class, 'index'])->name('tasks.index');

Route::get('/dashboard', [TaskController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

    
Route::prefix('issues/{issue}')->middleware('auth')->group(function () {
    Route::get('comments',     [IssueCommentController::class, 'index'])->name('issues.comments.index');
    Route::post('comments',    [IssueCommentController::class, 'store'])->name('issues.comments.store');
    Route::delete('comments/{comment}', [IssueCommentController::class, 'destroy'])->name('issues.comments.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/tasks/{task}/comments',                [TaskCommentController::class, 'index'])->name('tasks.comments.index');
    Route::post('/tasks/{task}/comments',                [TaskCommentController::class, 'store'])->name('tasks.comments.store');
    Route::delete('/tasks/{task}/comments/{comment}',      [TaskCommentController::class, 'destroy'])->name('tasks.comments.destroy');
});

Route::middleware('auth')->group(function () {
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])
        ->name('tasks.updateStatus')
        ->middleware('auth');
    Route::resource('issues', IssueController::class);
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
