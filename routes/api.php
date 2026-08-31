<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    AppVersionController,
    RfidController,
    ArticleController,
    AnnouncementController,
    GradeSheetController,
    SchoolYearController,
    ClassDetailController,
    ClassScheduleController,
    PaymentRegistionController,
    NotificationController,
    SiblingController,
    SchoolCalendarController
};

Route::get('/check-app-update', [AppVersionController::class, 'check']);

Route::post('/login', [AuthController::class, 'login']);

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::get('/user', [AuthController::class, 'userData']);
    Route::put('/user', [AuthController::class, 'update']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/siblings', [SiblingController::class, 'index'])->name('siblings.index');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
    Route::post('notifications/{id}/unread', [NotificationController::class, 'markUnread'])->name('notifications.mark-unread');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
    Route::post('notifications/unread-all', [NotificationController::class, 'markAllUnread'])->name('notifications.mark-all-unread');

    Route::prefix('rfid')->group(function () {
        Route::get('/', [RfidController::class, 'index']);
    });

    Route::prefix('articles')->group(function () {
        Route::get('/', [ArticleController::class, 'index']);
        Route::get('/{id}', [ArticleController::class, 'show']);
    });

    Route::prefix('announcements')->group(function () {
        Route::get('/', [AnnouncementController::class, 'index'])->name('announcements.index');
        Route::post('/read-all', [AnnouncementController::class, 'markAllRead'])->name('announcements.mark-all-read');
        Route::get('/{id}', [AnnouncementController::class, 'show'])->name('announcements.show');
        Route::post('/{id}/read', [AnnouncementController::class, 'markRead'])->name('announcements.mark-read');
        Route::post('/{id}/unread', [AnnouncementController::class, 'markUnread'])->name('announcements.mark-unread');
        Route::post('/{id}/archive', [AnnouncementController::class, 'markArchived'])->name('announcements.mark-archived');
    });

    Route::prefix('grade-sheets')->group(function () {
        Route::get('/', [GradeSheetController::class, 'index']);
        Route::get('/school-years', [GradeSheetController::class, 'schoolYears']);
        Route::get('/{id}', [GradeSheetController::class, 'show']);
    });

    Route::prefix('school-years')->group(function () {
        Route::get('/', [SchoolYearController::class, 'index']);
        Route::get('/{id}', [SchoolYearController::class, 'show']);
    });

    Route::prefix('class-details')->group(function () {
        Route::get('/', [ClassDetailController::class, 'index']);
    });

    Route::prefix('class-schedules')->group(function () {
        Route::get('/', [ClassScheduleController::class, 'index'])->name('class-schedules.index');
    });

    Route::prefix('payment-registrations')->group(function () {
        Route::get('/', [PaymentRegistionController::class, 'index'])->name('payment-registrations.index');
        Route::get('/status', [PaymentRegistionController::class, 'status'])->name('payment-registrations.status');
        Route::post('/', [PaymentRegistionController::class, 'store'])->name('payment-registrations.store');
        Route::get('/transaction-logs', [PaymentRegistionController::class, 'transactionLogs'])->name('payment-registrations.transaction-logs');
    });

    Route::prefix('school-calendar')->group(function () {
        Route::get('/', [SchoolCalendarController::class, 'index'])->name('school-calendar.index');
        Route::get('/{id}', [SchoolCalendarController::class, 'show'])->name('school-calendar.show');
    });
});
