<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Chatbot\ChatbotController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExcelUploadController;
use App\Http\Controllers\Form\FormFieldController;
use App\Http\Controllers\Form\FormSubmissionController;
use App\Http\Controllers\Form\FormTemplateController;
use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\Report\ReportController;
use App\Http\Controllers\Report\ReportTemplateController;
use App\Http\Controllers\Report\ReportVersionController;
use App\Http\Controllers\Admin\OrgUnitController;
use App\Http\Controllers\Admin\JobPositionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Audit\AuditLogController;
use App\Http\Controllers\DecisionSupportController;
use App\Http\Controllers\Form\FormExcelController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Landing Page (public)
|--------------------------------------------------------------------------
*/
Route::get('/', \App\Http\Controllers\LandingController::class)->name('landing');

// PWA: صفحة عدم الاتصال (عامة، بلا بيانات حساسة)
Route::view('/offline', 'offline')->name('offline');

/*
|--------------------------------------------------------------------------
| Guest Routes (Auth)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| 2FA Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/2fa/setup', [TwoFactorController::class, 'showSetupForm'])->name('2fa.setup');
    Route::post('/2fa/enable', [TwoFactorController::class, 'enable'])->middleware('throttle:10,1')->name('2fa.enable');
    Route::get('/2fa/challenge', [TwoFactorController::class, 'showVerifyForm'])->name('2fa.challenge');
    Route::post('/2fa/verify', [TwoFactorController::class, 'verify'])->middleware('throttle:10,1')->name('2fa.verify');
    Route::get('/2fa/recovery-codes', [TwoFactorController::class, 'showRecoveryCodes'])->middleware('check.2fa')->name('2fa.recovery');
    Route::post('/2fa/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->middleware(['check.2fa', 'throttle:5,1'])->name('2fa.recovery.regenerate');
    Route::post('/2fa/disable', [TwoFactorController::class, 'disable'])->middleware('check.2fa')->name('2fa.disable');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'ensure.user.active', 'check.2fa'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Global search + user manual
    Route::get('/search', [SearchController::class, 'index'])->name('search');
    Route::view('/manual', 'manual.index')->name('manual');

    // Interactive map (spatial + descriptive data)
    Route::middleware('permission:view_map')->prefix('map')->name('map.')->group(function () {
        Route::get('/', [MapController::class, 'index'])->name('index');
        Route::get('/center-data', [MapController::class, 'centerData'])->name('center-data');
        Route::get('/layers', [MapController::class, 'layers'])->name('layers');
        Route::get('/features', [MapController::class, 'features'])->name('features');
        Route::get('/units', [MapController::class, 'units'])->name('units');
        Route::get('/unit/{orgUnit}', [MapController::class, 'unit'])->name('unit');
        Route::get('/search', [MapController::class, 'search'])->name('search');
        Route::get('/export', [MapController::class, 'export'])->name('export');
    });

    // Decision support for leaders (AI)
    Route::middleware('permission:use_decision_support')->prefix('decision-support')->name('decision.')->group(function () {
        Route::get('/', [DecisionSupportController::class, 'index'])->name('index');
        Route::get('/insights', [DecisionSupportController::class, 'insights'])->name('insights');
        Route::post('/ask', [DecisionSupportController::class, 'ask'])->name('ask');
        Route::get('/brief', [DecisionSupportController::class, 'brief'])->name('brief');
    });

    // Excel templates: download template / export data / re-upload to add or update
    Route::middleware('permission:import_export_excel')->prefix('excel')->name('excel.')->group(function () {
        Route::get('/', [FormExcelController::class, 'center'])->name('center');
        Route::get('/templates/{formTemplate}/download', [FormExcelController::class, 'downloadTemplate'])->name('template.download');
        Route::post('/templates/{formTemplate}/import', [FormExcelController::class, 'importNew'])->name('template.import');
        Route::get('/submissions/{formSubmission}/export', [FormExcelController::class, 'exportSubmission'])->name('submission.export');
        Route::post('/submissions/{formSubmission}/import', [FormExcelController::class, 'importSubmission'])->name('submission.import');
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'changePassword'])->name('profile.password');

    // Form Templates (manager, admin)
    Route::middleware('permission:manage_form_templates')->prefix('forms')->name('forms.')->group(function () {
        Route::get('/templates', [FormTemplateController::class, 'index'])->name('templates.index');
        Route::post('/templates', [FormTemplateController::class, 'store'])->name('templates.store');
        Route::get('/templates/create', [FormTemplateController::class, 'create'])->name('templates.create');
        Route::get('/templates/{formTemplate}', [FormTemplateController::class, 'show'])->name('templates.show');
        Route::put('/templates/{formTemplate}', [FormTemplateController::class, 'update'])->name('templates.update');
        Route::delete('/templates/{formTemplate}', [FormTemplateController::class, 'destroy'])->name('templates.destroy');
        Route::get('/templates/{formTemplate}/edit', [FormTemplateController::class, 'edit'])->name('templates.edit');
        Route::post('/templates/{formTemplate}/duplicate', [FormTemplateController::class, 'duplicate'])->name('templates.duplicate');

        // Form Fields within template
        Route::post('/templates/{formTemplate}/fields', [FormFieldController::class, 'store'])->name('fields.store');
        Route::put('/templates/{formTemplate}/fields/{field}', [FormFieldController::class, 'update'])->name('fields.update');
        Route::delete('/templates/{formTemplate}/fields/{field}', [FormFieldController::class, 'destroy'])->name('fields.destroy');
        Route::post('/templates/reorder', [FormFieldController::class, 'reorder'])->name('fields.reorder');
    });

    // Form Submissions (all authenticated users)
    Route::prefix('submissions')->name('submissions.')->group(function () {
        Route::get('/', [FormSubmissionController::class, 'index'])->name('index');
        Route::get('/create', [FormSubmissionController::class, 'create'])->name('create');
        Route::post('/', [FormSubmissionController::class, 'store'])->name('store');
        Route::get('/{formSubmission}', [FormSubmissionController::class, 'show'])->name('show');
        Route::post('/{formSubmission}/assign', [FormSubmissionController::class, 'assign'])->name('assign');
        Route::post('/{formSubmission}/data', [FormSubmissionController::class, 'submitData'])->name('data');
        Route::get('/{formSubmission}/data-entry', [FormSubmissionController::class, 'dataEntry'])->name('data-entry');
        Route::post('/{formSubmission}/review', [FormSubmissionController::class, 'review'])->name('review');
        Route::post('/{formSubmission}/approve', [FormSubmissionController::class, 'approve'])->name('approve');
        Route::post('/{formSubmission}/reject', [FormSubmissionController::class, 'reject'])->name('reject');
        Route::post('/{formSubmission}/revision', [FormSubmissionController::class, 'sendBackForRevision'])->name('revision');
        Route::post('/{formSubmission}/approve-report', [FormSubmissionController::class, 'approveAndCreateReport'])->name('approve-report');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/export/excel', [ReportController::class, 'exportExcel'])->name('export-excel');
        Route::get('/export/pdf', [ReportController::class, 'downloadPdf'])->name('export-pdf');
        Route::post('/', [ReportController::class, 'store'])->name('store');
        Route::get('/create', [ReportController::class, 'create'])->name('create');
        Route::get('/{report}', [ReportController::class, 'show'])->name('show');
        Route::get('/{report}/edit', [ReportController::class, 'edit'])->name('edit');
        Route::put('/{report}', [ReportController::class, 'update'])->name('update');
        Route::post('/{report}/generate-ai', [ReportController::class, 'generateAiContent'])->name('generate-ai');
        Route::post('/{report}/approve', [ReportController::class, 'approveReport'])->name('approve');
        Route::post('/{report}/reject', [ReportController::class, 'rejectReport'])->name('reject');
        Route::post('/{report}/send', [ReportController::class, 'sendReport'])->name('send');
        Route::get('/{report}/pdf', [ReportController::class, 'showPdf'])->name('pdf');
        Route::get('/{report}/excel', [ReportController::class, 'downloadExcel'])->name('excel');

        // Report Versions
        Route::get('/{report}/versions', [ReportVersionController::class, 'index'])->name('versions.index');
        Route::get('/{report}/versions/{version}', [ReportVersionController::class, 'show'])->name('versions.show');
        Route::post('/{report}/versions/compare', [ReportVersionController::class, 'compare'])->name('versions.compare');
        Route::post('/{report}/versions/{version}/revert', [ReportVersionController::class, 'revert'])->name('versions.revert');
    });

    // Report Templates (manager, admin)
    Route::middleware('permission:manage_reports')->prefix('report-templates')->name('report-templates.')->group(function () {
        Route::get('/', [ReportTemplateController::class, 'index'])->name('index');
        Route::post('/', [ReportTemplateController::class, 'store'])->name('store');
        Route::get('/create', [ReportTemplateController::class, 'create'])->name('create');
        Route::get('/{reportTemplate}', [ReportTemplateController::class, 'show'])->name('show');
        Route::get('/{reportTemplate}/edit', [ReportTemplateController::class, 'edit'])->name('edit');
        Route::put('/{reportTemplate}', [ReportTemplateController::class, 'update'])->name('update');
        Route::delete('/{reportTemplate}', [ReportTemplateController::class, 'destroy'])->name('destroy');
    });

    // Chatbot
    Route::get('/chatbot', [ChatbotController::class, 'index'])->name('chatbot.index');
    Route::post('/chatbot/send', [ChatbotController::class, 'sendMessage'])->name('chatbot.send');
    Route::get('/chatbot/history', [ChatbotController::class, 'getHistory'])->name('chatbot.history');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');

    // Excel Upload & Data
    Route::middleware('permission:manage_data_uploads')->prefix('upload')->name('excel-upload.')->group(function () {
        Route::get('/', [ExcelUploadController::class, 'index'])->name('index');
        Route::get('/vehicles', [ExcelUploadController::class, 'vehicles'])->name('vehicles');
        Route::get('/maintenance', [ExcelUploadController::class, 'maintenance'])->name('maintenance');
        Route::get('/strategic', [ExcelUploadController::class, 'strategic'])->name('strategic');
        Route::get('/sectors/{sector}', [ExcelUploadController::class, 'sector'])->name('sector');
        Route::get('/facilities/{module}', [ExcelUploadController::class, 'facilities'])->name('facilities');
    });
});

/*
|--------------------------------------------------------------------------
| Admin Routes (permission based — roles are editable from the UI)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'ensure.user.active', 'check.2fa'])->prefix('admin')->name('admin.')->group(function () {
    // Legacy structure screens
    Route::middleware('permission:manage_org_structure')->group(function () {
        Route::resource('governorates', \App\Http\Controllers\Admin\GovernorateController::class)->except(['show']);
        Route::resource('sectors', \App\Http\Controllers\Admin\SectorController::class)->except(['show']);
        Route::resource('departments', \App\Http\Controllers\Admin\DepartmentController::class)->except(['show']);
        Route::resource('centers', \App\Http\Controllers\Admin\CenterController::class)->except(['show']);
        Route::resource('divisions', \App\Http\Controllers\Admin\DivisionController::class)->except(['show']);

        // Unified organisational structure (governorate → ministries/directorates/centers → cities/villages …)
        Route::get('org-units/tree', [OrgUnitController::class, 'tree'])->name('org-units.tree');
        Route::get('org-units/export', [OrgUnitController::class, 'export'])->name('org-units.export');
        Route::get('org-units/template', [OrgUnitController::class, 'template'])->name('org-units.template');
        Route::post('org-units/import', [OrgUnitController::class, 'import'])->name('org-units.import');
        Route::post('org-units/import-administrative', [OrgUnitController::class, 'importAdministrative'])->name('org-units.import-administrative');
        Route::post('org-units/{orgUnit}/move', [OrgUnitController::class, 'move'])->name('org-units.move');
        Route::resource('org-units', OrgUnitController::class)->parameters(['org-units' => 'orgUnit']);
    });

    // Job structure (الهيكل الوظيفي)
    Route::middleware('permission:manage_positions')->group(function () {
        Route::get('positions/export', [JobPositionController::class, 'export'])->name('positions.export');
        Route::get('positions/template', [JobPositionController::class, 'template'])->name('positions.template');
        Route::post('positions/import', [JobPositionController::class, 'import'])->name('positions.import');
        Route::post('positions/{position}/assign', [JobPositionController::class, 'assign'])->name('positions.assign');
        Route::resource('positions', JobPositionController::class)->except(['create', 'edit', 'show']);
    });

    // Accounts
    Route::middleware('permission:manage_users')->group(function () {
        Route::get('users/export', [\App\Http\Controllers\Admin\UserController::class, 'export'])->name('users.export');
        Route::get('users/template', [\App\Http\Controllers\Admin\UserController::class, 'template'])->name('users.template');
        Route::post('users/import', [\App\Http\Controllers\Admin\UserController::class, 'import'])->name('users.import');
        Route::post('users/{user}/toggle', [\App\Http\Controllers\Admin\UserController::class, 'toggle'])->name('users.toggle');
        Route::post('users/{user}/reset-2fa', [TwoFactorController::class, 'adminReset'])->name('users.reset-2fa');
        Route::post('users/{user}/reset-password', [\App\Http\Controllers\Admin\UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::post('users/{user}/assign-role', [\App\Http\Controllers\Admin\UserController::class, 'assignRole'])->name('users.assign-role');
        Route::resource('users', \App\Http\Controllers\Admin\UserController::class)->except(['show']);
    });

    // Roles & permissions
    Route::middleware('permission:manage_roles')->group(function () {
        Route::put('roles/matrix', [RoleController::class, 'updateMatrix'])->name('roles.matrix');
        Route::resource('roles', RoleController::class)->except(['show']);
    });

    // Audit log
    Route::middleware('permission:view_audit_logs')->group(function () {
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');
    });
});
