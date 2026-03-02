<?php

use App\Http\Controllers\GroupController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProjectController;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/add-user', function () {
    $user = User::find(11);
    $user->assignRole('juridico');
    $role = $user->role('juridico')->get();

    dump($role);
    dd($user);
});

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

//
Route::get('/editais', [UserController::class, 'index'])->middleware(['auth', 'verified'])->name('editais.index');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/grupos', [GroupController::class, 'index'])->name('groups.index');
    Route::put('/grupos', [GroupController::class, 'update'])->name('groups.update');
    Route::resource('projects', ProjectController::class);
});

require __DIR__.'/auth.php';
