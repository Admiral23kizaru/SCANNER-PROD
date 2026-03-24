<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdminProfileController;
use App\Http\Controllers\Api\AdminStudentController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\IdCardController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\SectionController;
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
    // Protocol Comment: Source: API Router; Destination: AttendanceController; Function: Creating the bridge for QR scanning.
    Route::post('/attendance/scan', [AttendanceController::class, 'scan']);
    Route::get('/attendance/public/recent', 'publicRecent');
    Route::get('/attendance/public/stats', 'publicStats');  // public stats for Guard Terminal
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
            Route::get('/dashboard/analytics', 'getPopulationDetails');
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

        Route::controller(AdminProfileController::class)->group(function () {
            Route::get('/profile', 'show');
            Route::put('/update-profile', 'update');
            Route::post('/update-profile/photo', 'uploadPhoto');
            Route::put('/update-profile/password', 'changePassword');
        });

        // Section management routes
        Route::controller(SectionController::class)->prefix('sections')->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'destroy');
            Route::post('/{id}/assign-students', 'assignStudents');
            Route::get('/unassigned-students', 'unassignedStudents');
            Route::get('/teachers-list', 'teachers');
        });
    });

    /* ------------------------------------------------------------------ */
    /*  Teacher panel                                                      */
    /* ------------------------------------------------------------------ */

    Route::middleware('role:Teacher')->prefix('teacher')->group(function () {

        Route::get('/dashboard', fn () => response()->json(['message' => 'Teacher dashboard']));

        Route::controller(StudentController::class)->group(function () {
            Route::get('/students', 'index');
            Route::post('/students/import', 'import');
            Route::post('/students', 'store');
            Route::put('/students/{id}', 'update');
            Route::post('/students/{id}', 'update');
            Route::post('/students/{id}/photo', 'uploadPhoto');
        });

        /*
         * Target Role: Attendance Guard / Parent.
         * Source: QR Scanner (Teacher Dashboard).
         * Function: Authenticated scan route — preference-based routing (SMS vs Email).
         * Destination: Guardian contact (SMS via Semaphore or Email via PHPMailer).
         */
        Route::controller(AttendanceController::class)->group(function () {
            Route::post('/attendance/scan', 'teacherScan');    // teacher-side QR scan
            Route::get('/attendance/recent', 'recent'); // teacher's own scan history
            Route::get('/attendance/monitor', 'getTeacherStudentStatus'); // split-view monitor
        });

        Route::controller(\App\Http\Controllers\Api\TeacherProfileController::class)->group(function () {
            Route::get('/profile', 'show');
            Route::put('/update-profile', 'update');
            Route::post('/update-profile/photo', 'uploadPhoto');
            Route::put('/update-profile/password', 'changePassword');
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
