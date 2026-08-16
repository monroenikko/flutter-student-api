<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    RfidController,
    ArticleController,
    GradeSheetController,
    SchoolYearController,
    ClassDetailController,
    PaymentRegistionController,
    NotificationController
};

Route::post('/login', [AuthController::class, 'login']);

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::get('/user', [AuthController::class, 'userData']);
    Route::put('/user', [AuthController::class, 'update']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);

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

    Route::prefix('payment-registrations')->group(function () {
        Route::get('/', [PaymentRegistionController::class, 'index']);
    });
});
