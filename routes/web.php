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

    // User management
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::post('/users/{user}/toggle', [UserController::class, 'toggleStatus'])->name('users.toggle');
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::post('/users/invitations/{invitation}/resend', [UserController::class, 'resendInvitation'])->name('users.invitations.resend');
    Route::post('/users/invitations/{invitation}/cancel', [UserController::class, 'cancelInvitation'])->name('users.invitations.cancel');

    // Audit logs
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    // Profile settings
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
