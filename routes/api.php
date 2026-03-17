<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdminStudentController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AdminProfileController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\IdCardController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TeacherManagementController;
use Illuminate\Support\Facades\Route;

/* ====================================================================== */
/*  Public (unauthenticated) routes                                       */
/* ====================================================================== */

Route::controller(AuthController::class)->group(function () {
    Route::post('/login', 'login');
});

Route::controller(PasswordResetController::class)->prefix('password')->group(function () {
    Route::post('/request-otp', 'requestOtp');
    Route::post('/verify-otp', 'verifyOtp');
    Route::post('/reset', 'reset');
});

Route::controller(AttendanceController::class)->group(function () {
    Route::post('/attendance/scan', 'scanPublic');
    Route::get('/attendance/public/recent', 'publicRecent');
});

/* ====================================================================== */
/*  Signed media routes (no auth — URL expiry takes care of security)     */
/* ====================================================================== */


Route::controller(IdCardController::class)->group(function () {
    Route::get('/media/id/{hash}', 'generateSecure')->name('id.download')->middleware('signed');
    Route::get('/media/teacher-id/{hash}', 'generateTeacherSecure')->name('teacher-id.download')->middleware('signed');
});

/* ====================================================================== */
/*  Authenticated routes (Sanctum)                                        */
/* ====================================================================== */

// Secure signed route without auth middleware since it's signed with expiration
Route::get('/media/id/{hash}', [IdCardController::class, 'generateSecure'])
    ->name('id.download')
    ->middleware('signed');


Route::middleware('auth:sanctum')->group(function () {

    Route::controller(AuthController::class)->group(function () {
        Route::post('/logout', 'logout');
        Route::get('/user', 'user');
    });

    /* ------------------------------------------------------------------ */
    /*  ID-card signed URL generators (Teacher + Admin)                    */
    /* ------------------------------------------------------------------ */

    Route::controller(IdCardController::class)->group(function () {
        Route::get('/teacher/students/{id}/id-url', 'getSignedUrl')->middleware('role:Teacher');
        Route::get('/admin/students/{id}/id-url', 'getSignedUrl')->middleware('role:Admin');
        Route::get('/admin/teachers/{id}/id-url', 'getTeacherSignedUrl')->middleware('role:Admin');
    });

    /* ------------------------------------------------------------------ */
    /*  Admin panel                                                        */
    /* ------------------------------------------------------------------ */

    Route::middleware('role:Admin')->prefix('admin')->group(function () {


        Route::controller(AdminController::class)->group(function () {
            Route::get('/dashboard', 'dashboard');
        });

        Route::controller(StatsController::class)->group(function () {
            Route::get('/stats', 'index');
            Route::get('/dashboard/stats', 'dashboardStats');
            Route::get('/dashboard/overview', 'overview');
            Route::get('/attendance/trends', 'attendanceTrends');
            Route::get('/reports/summary-pdf', 'summaryReportPdf');
        });

        Route::controller(TeacherManagementController::class)->prefix('teachers')->group(function () {
            Route::get('/export', 'export');
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::put('/{id}', 'update');
            Route::post('/{id}/photo', 'uploadPhoto');
            Route::delete('/{id}', 'destroy');
        });

        Route::controller(AdminStudentController::class)->prefix('students')->group(function () {
            Route::get('/export', 'export');
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'destroy');
        });

        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/stats', [StatsController::class, 'index']);
        Route::get('/dashboard/stats', [StatsController::class, 'dashboardStats']);
        Route::get('/attendance/trends', [StatsController::class, 'attendanceTrends']);
        Route::get('/dashboard/overview', [StatsController::class, 'overview']);
        Route::get('/reports/summary-pdf', [StatsController::class, 'summaryReportPdf']);
        Route::get('/teachers/export', [TeacherManagementController::class, 'export']);
        Route::get('/teachers', [TeacherManagementController::class, 'index']);
        Route::post('/teachers', [TeacherManagementController::class, 'store']);
        Route::put('/teachers/{id}', [TeacherManagementController::class, 'update']);
        Route::post('/teachers/{id}/photo', [TeacherManagementController::class, 'uploadPhoto']);
        Route::delete('/teachers/{id}', [TeacherManagementController::class, 'destroy']);
        Route::get('/students/export', [AdminStudentController::class, 'export']);
        Route::get('/students', [AdminStudentController::class, 'index']);
        Route::post('/students', [AdminStudentController::class, 'store']);
        Route::put('/students/{id}', [AdminStudentController::class, 'update']);
        Route::delete('/students/{id}', [AdminStudentController::class, 'destroy']);

        Route::get('/profile', [AdminProfileController::class, 'show']);
        Route::put('/profile', [AdminProfileController::class, 'update']);
        Route::post('/profile/photo', [AdminProfileController::class, 'uploadPhoto']);
        Route::put('/profile/password', [AdminProfileController::class, 'changePassword']);

    });

    /* ------------------------------------------------------------------ */
    /*  Teacher panel                                                      */
    /* ------------------------------------------------------------------ */

    Route::middleware('role:Teacher')->prefix('teacher')->group(function () {

        Route::get('/dashboard', fn () => response()->json(['message' => 'Teacher dashboard']));

        Route::controller(StudentController::class)->prefix('students')->group(function () {
            Route::get('/', 'index');
            Route::post('/import', 'import');
            Route::post('/', 'store');
            Route::put('/{id}', 'update');
            Route::post('/{id}', 'update');
            Route::post('/{id}/photo', 'uploadPhoto');
        });
    });

    /* ------------------------------------------------------------------ */
    /*  Guard / Scanner panel                                              */
    /* ------------------------------------------------------------------ */

    Route::middleware('role:Guard,Admin,Teacher')->prefix('guard')->group(function () {
        Route::get('/dashboard', fn () => response()->json(['message' => 'Guard dashboard']));
        Route::get('/stats', [AttendanceController::class, 'getStats']);
    });
});
