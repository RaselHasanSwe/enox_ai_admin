<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HandoffController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('enox.auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
    Route::get('/conversations/export', [ConversationController::class, 'export'])->name('conversations.export');
    Route::get('/conversations/{id}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::post('/conversations/{id}/tags', [ConversationController::class, 'updateTags'])->name('conversations.tags');

    Route::get('/settings/business-hours', [SettingsController::class, 'businessHours'])->name('settings.business-hours');
    Route::put('/settings/business-hours', [SettingsController::class, 'updateBusinessHours'])->name('settings.business-hours.update');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{id}', [UserController::class, 'show'])->name('users.show');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    Route::get('/handoff', [HandoffController::class, 'queue'])->name('handoff.queue');
    Route::get('/handoff/active', [HandoffController::class, 'active'])->name('handoff.active');
    Route::post('/handoff/{id}/claim', [HandoffController::class, 'claim'])->name('handoff.claim');
    Route::get('/handoff/{id}/chat', [HandoffController::class, 'chat'])->name('handoff.chat');
    Route::post('/handoff/{id}/message', [HandoffController::class, 'sendMessage'])->name('handoff.message');
    Route::post('/handoff/{id}/release', [HandoffController::class, 'release'])->name('handoff.release');
    Route::post('/handoff/{id}/resolve', [HandoffController::class, 'resolve'])->name('handoff.resolve');
    Route::post('/presence', [HandoffController::class, 'presence'])->name('presence');

    Route::get('/inquiries', [InquiryController::class, 'index'])->name('inquiries.index');
    Route::get('/inquiries/{id}', [InquiryController::class, 'show'])->name('inquiries.show');
    Route::patch('/inquiries/{id}/status', [InquiryController::class, 'updateStatus'])->name('inquiries.status');
});
