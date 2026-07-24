<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\LicenseFileController;
use App\Http\Controllers\ReminderHistoryController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Redirect root to dashboard (or login if not authenticated)
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// -----------------------------------------------------------------------
// All application routes require authentication
// -----------------------------------------------------------------------

Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // License CRUD
    Route::resource('licenses', LicenseController::class);

    // File download (authenticated, serves from private disk)
    Route::get('licenses/{license}/files/{file}/download', [LicenseFileController::class, 'download'])
        ->name('licenses.files.download');

    // File delete
    Route::delete('licenses/{license}/files/{file}', [LicenseFileController::class, 'destroy'])
        ->name('licenses.files.destroy');

    // Reminder History
    Route::get('/reminders', [ReminderHistoryController::class, 'index'])->name('reminders.index');
    Route::post('/reminders/{message}/retry', [ReminderHistoryController::class, 'retry'])->name('reminders.retry');
    
    // Manual dispatch triggers
    Route::post('/reminders/dispatch-now', [ReminderHistoryController::class, 'dispatchNow'])->name('reminders.dispatch-now');
    Route::post('/licenses/{license}/reminders/send-now', [ReminderHistoryController::class, 'sendNow'])->name('licenses.reminders.send-now');

    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/test', [SettingController::class, 'sendTest'])->name('settings.test');
    Route::post('/settings/fonnte-token', [SettingController::class, 'updateFonnteToken'])->name('settings.fonnte-token');
    Route::post('/settings/fonnte-check', [SettingController::class, 'checkFonnteDevice'])->name('settings.fonnte-check');

    // Message Templates settings
    Route::post('/settings/templates', [\App\Http\Controllers\MessageTemplateController::class, 'store'])->name('settings.templates.store');
    Route::put('/settings/templates/{template}', [\App\Http\Controllers\MessageTemplateController::class, 'update'])->name('settings.templates.update');
    Route::delete('/settings/templates/{template}', [\App\Http\Controllers\MessageTemplateController::class, 'destroy'])->name('settings.templates.destroy');
    Route::post('/settings/templates/{template}/default', [\App\Http\Controllers\MessageTemplateController::class, 'setDefault'])->name('settings.templates.default');

    // Admin management ("Kelola Admin") — super admin only
    Route::middleware(['auth', 'superadmin'])->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users/{user}/approve', [UserController::class, 'approve'])->name('users.approve');
        Route::post('/users/{user}/reject', [UserController::class, 'reject'])->name('users.reject');
        Route::post('/users/{user}/toggle', [UserController::class, 'toggleStatus'])->name('users.toggle');
        Route::post('/users/{user}/make-super-admin', [UserController::class, 'makeSuperAdmin'])->name('users.make-super-admin');
        Route::post('/users/{user}/send-reset-link', [UserController::class, 'sendResetLink'])->name('users.send-reset-link');
    });

    // Audit logs
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    // Profile settings
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
