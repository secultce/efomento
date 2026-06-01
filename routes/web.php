<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\LegalAnalysisController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OpeningController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectStageController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/add-user/{id}/{role}', function ($id, $role) {
    $user = User::find($id);
    $user->assignRole($role);
    $role = $user->role($role)->get();
    dump($role);
    dd($user);
});

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::resource('editais', NoticeController::class)
        ->parameters([
            'editais' => 'notice',
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
    Route::get('/editais/{notice}/audits', [NoticeController::class, 'audits'])
        ->name('notices.audits');

    Route::resource('projetos', ProjectController::class)
        ->parameters([
            'projetos' => 'project',
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
    Route::post('/projetos/criar-tc', [ProjectController::class, 'createTC'])
        ->name('projects.create-tc');
    Route::get('/projetos/documentos/{document}/download', [DocumentController::class, 'download'])
        ->name('documents.download');
    Route::post('/projetos/documentos/download-zip', [DocumentController::class, 'downloadZip'])
        ->name('documents.download-zip');
    Route::patch('/projetos/{project}/abertura/{opening}/atualizar', [OpeningController::class, 'update'])
        ->scopeBindings()
        ->name('projects.openings.update');
    Route::post('/projetos/{project}/etapas/{stage}/devolver', [ProjectStageController::class, 'return'])
        ->scopeBindings()
        ->name('projects.stages.return');
    Route::patch('/projetos/{project}/etapas/{stage}/tramitar', [ProjectStageController::class, 'advance'])
        ->scopeBindings()
        ->name('projects.stages.advance');
    Route::get('editais/{notice}/projetos', [ProjectController::class, 'index'])
        ->name('notices.projects');
    Route::get('editais/{notice}/projetos/{project}', [ProjectController::class, 'projectDetail'])
        ->scopeBindings()
        ->name('notices.projects.show');
    Route::prefix('notificacoes')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/nao-lidas', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
        Route::patch('/{id}/ler', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
        Route::patch('/ler-todas', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    });
    Route::get('/projetos/{project}/analise-juridica', [LegalAnalysisController::class, 'index'])
        ->name('legal-analysis.index');
    Route::get('/projetos/{project}/analise-juridica/arquivos/{file}', [LegalAnalysisController::class, 'serveFile'])
        ->scopeBindings()
        ->name('legal-analysis.files.serve');
    Route::put('/projetos/{project}/analise-juridica/arquivos/{file}', [LegalAnalysisController::class, 'updateFileStatus'])
        ->scopeBindings()
        ->name('legal-analysis.update-status');

});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/grupos', [GroupController::class, 'index'])->name('groups.index');
    Route::put('/grupos', [GroupController::class, 'update'])->name('groups.update');
});

require __DIR__.'/auth.php';
