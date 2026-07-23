<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\PasswordController;
use Illuminate\Support\Facades\Route;

// -----------------------------------------------------------------------
// Guest routes — registration deliberately removed (admin-only app).
// Accounts are created via AdminSeeder only.
// -----------------------------------------------------------------------

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Admin Invitation Accept routes
    Route::get('invitation/{token}', [\App\Http\Controllers\Auth\InvitationController::class, 'show'])
        ->name('invitation.show');
    Route::post('invitation/{token}/otp', [\App\Http\Controllers\Auth\InvitationController::class, 'requestOtp'])
        ->name('invitation.otp.request');
    Route::post('invitation/{token}/verify', [\App\Http\Controllers\Auth\InvitationController::class, 'verifyOtp'])
        ->name('invitation.otp.verify');
    Route::post('invitation/{token}/accept', [\App\Http\Controllers\Auth\InvitationController::class, 'savePassword'])
        ->name('invitation.accept');
    Route::post('invitation/{token}/cancel', [\App\Http\Controllers\Auth\InvitationController::class, 'cancelOtp'])
        ->name('invitation.cancel');

    // Forgot Password OTP routes
    Route::get('forgot-password', [\App\Http\Controllers\Auth\ForgotPasswordOtpController::class, 'showRequestForm'])
        ->name('password.request');
    Route::post('forgot-password', [\App\Http\Controllers\Auth\ForgotPasswordOtpController::class, 'sendResetOtp'])
        ->name('password.email');
    Route::get('forgot-password/verify', [\App\Http\Controllers\Auth\ForgotPasswordOtpController::class, 'showVerifyForm'])
        ->name('password.otp.verify');
    Route::post('forgot-password/verify', [\App\Http\Controllers\Auth\ForgotPasswordOtpController::class, 'verifyResetOtp'])
        ->name('password.otp.verify.post');
    Route::get('forgot-password/reset', [\App\Http\Controllers\Auth\ForgotPasswordOtpController::class, 'showResetForm'])
        ->name('password.otp.reset');
    Route::post('forgot-password/reset', [\App\Http\Controllers\Auth\ForgotPasswordOtpController::class, 'resetPassword'])
        ->name('password.otp.reset.post');
    Route::post('forgot-password/cancel', [\App\Http\Controllers\Auth\ForgotPasswordOtpController::class, 'cancelReset'])
        ->name('password.otp.cancel');
});

// -----------------------------------------------------------------------
// Authenticated routes
// -----------------------------------------------------------------------

Route::middleware('auth')->group(function () {
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
