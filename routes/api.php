<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdminStudentController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\IdCardController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TeacherManagementController;
use App\Http\Middleware\SanctumTokenFromQuery;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::post('/attendance/scan', [AttendanceController::class, 'scanPublic']);
Route::get('/attendance/public/recent', [AttendanceController::class, 'publicRecent']);

Route::get('/teacher/students/{id}/id-url', [IdCardController::class, 'getSignedUrl'])
    ->middleware(['auth:sanctum', 'role:Teacher']);

// Secure signed route without auth middleware since it's signed with expiration
Route::get('/media/id/{hash}', [IdCardController::class, 'generateSecure'])
    ->name('id.download')
    ->middleware('signed');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::middleware('role:Admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/stats', [StatsController::class, 'index']);
        Route::get('/teachers', [TeacherManagementController::class, 'index']);
        Route::post('/teachers', [TeacherManagementController::class, 'store']);
        Route::put('/teachers/{id}', [TeacherManagementController::class, 'update']);
        Route::post('/teachers/{id}/photo', [TeacherManagementController::class, 'uploadPhoto']);
        Route::delete('/teachers/{id}', [TeacherManagementController::class, 'destroy']);
        Route::get('/students', [AdminStudentController::class, 'index']);
        Route::post('/students', [AdminStudentController::class, 'store']);
        Route::put('/students/{id}', [AdminStudentController::class, 'update']);
        Route::delete('/students/{id}', [AdminStudentController::class, 'destroy']);
    });

    Route::middleware('role:Teacher')->prefix('teacher')->group(function () {
        Route::get('/dashboard', fn () => response()->json(['message' => 'Teacher dashboard']));
        Route::get('/students', [StudentController::class, 'index']);
        Route::post('/students', [StudentController::class, 'store']);
        Route::put('/students/{id}', [StudentController::class, 'update']);
        Route::post('/students/{id}', [StudentController::class, 'update']); // for _method=PUT spoofing with FormData
        Route::post('/students/{id}/photo', [StudentController::class, 'uploadPhoto']);
    });

    Route::middleware('role:Guard')->prefix('guard')->group(function () {
        Route::get('/dashboard', fn () => response()->json(['message' => 'Guard dashboard']));
    });

    Route::middleware('role:Guard')->prefix('attendance')->group(function () {
        Route::get('/recent', [AttendanceController::class, 'recent']);
    });
});
