<?php

use App\Http\Controllers\GroupController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\ProjectController;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/add-user/{id}/{role}', function ($id, $role) {
    $user = User::find($id);
    $user->assignRole($role);
    $role = $user->role($role)->get();
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


Route::middleware(['auth', 'verified'])->group(function () {

    Route::resource('editais', NoticeController::class)
        ->parameters([
            'editais' => 'notice'
        ])
        ->names([
            'index' => 'notices.index',
            'create' => 'notices.create',
            'store' => 'notices.store',
            'show' => 'notices.show',
            'edit' => 'notices.edit',
            'update' => 'notices.update',
            'destroy' => 'notices.destroy',
        ]);

    Route::resource('projetos', ProjectController::class)
        ->parameters([
            'projetos' => 'project'
        ])
        ->names([
            'index' => 'projects.index',
            'create' => 'projects.create',
            'store' => 'projects.store',
            'show' => 'projects.show',
            'edit' => 'projects.edit',
            'update' => 'projects.update',
            'destroy' => 'projects.destroy',
        ]);
    Route::post('/projetos/atribuir-fiscal', [ProjectController::class, 'assignProjectSupervisor'])
        ->name('projects.assign-supervisors');
    Route::post('/projetos/criar-ci', [ProjectController::class, 'createCI'])
        ->name('projects.create-ci');
    Route::get('editais/{notice}/projetos', [ProjectController::class, 'index'])
        ->name('notices.projects');
    Route::get('editais/{notice}/projetos/{project}', [ProjectController::class, 'projectDetail'])
        ->scopeBindings()
        ->name('notices.projects.show');
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/grupos', [GroupController::class, 'index'])->name('groups.index');
    Route::put('/grupos', [GroupController::class, 'update'])->name('groups.update');
});

require __DIR__.'/auth.php';
