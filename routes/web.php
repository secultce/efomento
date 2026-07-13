<?php

use App\Http\Controllers\BudgetController;
use App\Http\Controllers\DiligenceMessageController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FormalizationController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\InstallmentController;
use App\Http\Controllers\LegalAnalysisController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OpeningController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectStageController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

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
    Route::post('/projetos/criar-documento', [ProjectController::class, 'createDocument'])
        ->name('projects.create-document');
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
    Route::patch('/projetos/{project}/solicitar-proxima-parcela', [ProjectStageController::class, 'requestNextInstallment'])
        ->name('projects.stages.request-next-installment');
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
    Route::post('/editais/projetos/pagamento/import', [InstallmentController::class, 'import'])
        ->name('installments.import');
    Route::patch(
        '/projetos/{project}/parcelas/{installment}/observacao',
        [InstallmentController::class, 'updateRemark']
    )
        ->scopeBindings()
        ->name('projects.installments.remarks.update');
    Route::get('/projetos/{project}/analise-juridica', [LegalAnalysisController::class, 'index'])
        ->name('legal-analysis.index');
    Route::get('/projetos/{project}/analise-juridica/arquivos/{file}', [LegalAnalysisController::class, 'serveFile'])
        ->scopeBindings()
        ->name('legal-analysis.files.serve');
    Route::put('/projetos/{project}/analise-juridica/arquivos/{file}', [LegalAnalysisController::class, 'updateFileStatus'])
        ->scopeBindings()
        ->name('legal-analysis.update-status');

    Route::post('/projetos/{project}/formalizacao', [FormalizationController::class, 'store'])
        ->name('projects.formalizations.store');
    Route::patch('/projetos/{project}/formalizacao/{formalization}/atualizar', [FormalizationController::class, 'update'])
        ->scopeBindings()
        ->name('projects.formalizations.update');
    Route::get('/projetos/{project}/formalizacao/{formalization}/arquivos/{file}', [FormalizationController::class, 'showFile'])
        ->scopeBindings()
        ->name('projects.formalizations.files.show');
    Route::get('/projetos/{project}/formalizacao/{formalization}/arquivos/{file}/download', [FormalizationController::class, 'downloadFile'])
        ->scopeBindings()
        ->name('projects.formalizations.files.download');
    Route::delete('/projetos/{project}/formalizacao/{formalization}/arquivos/{file}', [FormalizationController::class, 'destroyFile'])
        ->scopeBindings()
        ->name('projects.formalizations.files.destroy');

    Route::post('/projetos/{project}/orcamento', [BudgetController::class, 'store'])
        ->name('projects.budgets.store');
    Route::patch('/projetos/{project}/orcamento/{budget}/atualizar', [BudgetController::class, 'update'])
        ->scopeBindings()
        ->name('projects.budgets.update');

    Route::post('/projetos/{project}/monitoramento', [MonitoringController::class, 'store'])
        ->name('projects.monitorings.store');
    Route::patch('/projetos/{project}/monitoramento/{monitoring}/atualizar', [MonitoringController::class, 'update'])
        ->scopeBindings()
        ->name('projects.monitorings.update');
    Route::get('/projetos/{project}/monitoramento/arquivos', [MonitoringController::class, 'files'])
        ->name('projects.monitorings.files');
    Route::get('/projetos/{project}/monitoramento/arquivos/{file}', [MonitoringController::class, 'serveFile'])
        ->name('projects.monitorings.files.serve');

    Route::get('/projetos/{project}/diligencias/{stage}', [DiligenceMessageController::class, 'index'])
        ->name('diligences.index');
    Route::post('/projetos/{project}/diligencias/{stage}', [DiligenceMessageController::class, 'store'])
        ->name('diligences.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/grupos', [GroupController::class, 'index'])->name('groups.index');
        Route::put('/grupos', [GroupController::class, 'update'])->name('groups.update');
        Route::post('/add-user/{user}/{role}', [UserController::class, 'assignRole'])
            ->name('users.assign-role');
        Route::delete('/remove-user-role/{user}/{role}', [UserController::class, 'removeRole'])
            ->name('users.remove-role');
    });
});

require __DIR__.'/auth.php';
