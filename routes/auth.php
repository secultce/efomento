<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\LoginCodeController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store'])->block();

    Route::get('two-factor-challenge', [LoginCodeController::class, 'show'])->block()->name('two-factor.show');
    Route::post('two-factor-challenge', [LoginCodeController::class, 'verify'])->block()->middleware('throttle:10,1')->name('two-factor.verify');
    Route::post('two-factor-challenge/resend', [LoginCodeController::class, 'resend'])->block()->middleware('throttle:1,1,two-factor-resend')->name('two-factor.resend');
    Route::post('two-factor-challenge/cancel', [LoginCodeController::class, 'cancel'])->block()->name('two-factor.cancel');

    // Keep existing bookmarks and already-open verification forms working.
    Route::get('login/code', fn () => redirect()->route('two-factor.show'));
    Route::post('login/code', [LoginCodeController::class, 'verify'])->block()->middleware('throttle:10,1');
    Route::post('login/code/resend', [LoginCodeController::class, 'resend'])->block()->middleware('throttle:1,1,two-factor-resend');
    Route::post('login/code/cancel', [LoginCodeController::class, 'cancel'])->block();

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->block()->name('logout');
});
