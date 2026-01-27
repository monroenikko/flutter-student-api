<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    RfidController,
    ArticleController,
    GradeSheetController,
    SchoolYearController,
    ClassDetailController
};

Route::post('/login', [AuthController::class, 'login']);

Route::group(['middleware' => ['auth:sanctum']], function() {

    Route::get('/user', [AuthController::class, 'userData']);
    Route::put('/user', [AuthController::class, 'update']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('rfid')->group(function () {
        Route::get('/', [RfidController::class, 'index']);
    });

    Route::prefix('articles')->group(function () {
        Route::get('/', [ArticleController::class, 'index']);
        Route::get('/{id}', [ArticleController::class, 'show']);
    });

    Route::prefix('grade-sheets')->group(function () {
        Route::get('/', [GradeSheetController::class, 'index']);
        Route::get('/{id}', [GradeSheetController::class, 'show']);
    });

    Route::prefix('school-years')->group(function () {
        Route::get('/', [SchoolYearController::class, 'index']);
        Route::get('/{id}', [SchoolYearController::class, 'show']);
    });

    Route::prefix('class-details')->group(function() {
        Route::get('/', [ClassDetailController::class, 'index']);
    });

});
