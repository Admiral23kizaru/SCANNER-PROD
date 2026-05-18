<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdminProfileController;
use App\Http\Controllers\Api\AdminStudentController;
use App\Http\Controllers\Api\AdminStudentSubjectController;
use App\Http\Controllers\Api\AdminSubjectController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LearningAssessmentController;
use App\Http\Controllers\Api\GuardAuthController;
use App\Http\Controllers\Api\IdCardController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\SetupController;
use App\Http\Controllers\Api\SchoolController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\SectionController;
use App\Http\Controllers\Api\SystemAdminController;
use App\Http\Controllers\Api\TeacherManagementController;
use Illuminate\Support\Facades\Route;
use App\Services\SchoolResolver;

/* ====================================================================== */
/*  Public (unauthenticated) routes                                       */
/* ====================================================================== */

Route::controller(AuthController::class)->group(function () {
    Route::post('/login', 'login');
});

// 1. Public endpoint — Guard Terminal fetches school name for display
Route::get('/school/{id}/info', function ($id) {
    $school = \App\Models\School::findOrFail($id);
    return response()->json(['id' => $school->id, 'name' => $school->name]);
});

// 2. Public endpoint — Guard Terminal resolves DepEd school ID to internal school_id + name
Route::get('/school/by-deped-id/{depedId}', function ($depedId) {
    $school = app(SchoolResolver::class)->resolveForScanUpWrite($depedId);

    if (! $school) {
        abort(404);
    }

    return response()->json([
        'id'   => $school->id,
        'name' => $school->name,
    ]);
});

// PUBLIC — BAT Step 1: check DepEd school ID before POST /guard/login
Route::get('/school/check/{deped_id}', [SchoolController::class, 'check']);

// 2. Bat file launcher — register a new school, admin user, settings, and school year
Route::post('/setup/register-school', [SetupController::class, 'registerSchool'])
    ->middleware('setup.secret');

// PUBLIC — BAT file login (no auth)
Route::post('/guard/login', [GuardAuthController::class, 'login'])
    ->middleware('throttle:30,1');

// PUBLIC — scanner attendance (Guard Terminal kiosk; Bearer optional after bat login)
Route::post('/attendance/scan', [AttendanceController::class, 'scanPublic'])
    ->middleware('throttle:240,1');

Route::controller(PasswordResetController::class)->prefix('password')->group(function () {
    Route::post('/request-otp', 'requestOtp');
    Route::post('/verify-otp', 'verifyOtp');
    Route::post('/reset', 'reset');
});

Route::controller(AttendanceController::class)->group(function () {
    Route::get('/attendance/public/recent', 'publicRecent')->middleware('throttle:120,1');
    Route::get('/attendance/public/stats', 'publicStats')->middleware('throttle:120,1');  // public stats for Guard Terminal
    Route::get('/attendance/public/division-stats', 'divisionPublicStats')->middleware('throttle:120,1');
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
    /*  System Admin panel (division-level, read-only monitoring)          */
    /* ------------------------------------------------------------------ */

    Route::middleware('role:System Admin')->prefix('system-admin')->group(function () {
        Route::get('/overview', [SystemAdminController::class, 'overview']);
        Route::get('/schools', [SystemAdminController::class, 'schools']);
        Route::get('/schools/export', [SystemAdminController::class, 'exportSchools']);
        Route::get('/schools/{depedSchoolId}', [SystemAdminController::class, 'schoolDetail']);
        Route::get('/schools/{depedSchoolId}/dashboard', [SystemAdminController::class, 'schoolDashboard']);
    });

    /* ------------------------------------------------------------------ */
    /*  Admin panel                                                        */
    /* ------------------------------------------------------------------ */

    Route::middleware('role:Admin,Reporting Manager')->prefix('admin')->group(function () {

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
            Route::get('/ehris', 'ehris');
            Route::post('/sync-ehris', 'syncEhris');
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::put('/{id}', 'update');
            Route::post('/{id}/photo', 'uploadPhoto');
            Route::delete('/{id}', 'destroy');
        });

        Route::controller(AdminStudentController::class)->prefix('students')->group(function () {
            Route::get('/export', 'export');
            Route::get('/', 'index');
            Route::get('/{id}/qr-context', 'qrContext');
            Route::post('/', 'store');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'destroy');
        });

        // Subjects management
        Route::controller(AdminSubjectController::class)->prefix('subjects')->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'destroy');
        });

        // Student subject enrollment
        Route::controller(AdminStudentSubjectController::class)->prefix('students')->group(function () {
            Route::get('/{id}/subjects', 'show');
            Route::put('/{id}/subjects', 'sync');
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
            Route::get('/{id}/students', 'students');
            Route::post('/{id}/assign-students', 'assignStudents');
            Route::post('/{id}/unassign-students', 'unassignStudents');
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

        Route::get('/sections', [SectionController::class, 'formOptions']);
        Route::get('/subjects', [AdminSubjectController::class, 'index']);
        Route::get('/students/{id}/subjects', [AdminStudentSubjectController::class, 'show']);
        Route::put('/students/{id}/subjects', [AdminStudentSubjectController::class, 'sync']);

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

        /* ------------------------------------------------------------------ */
        /*  Learning Assessment (scores + Excel export)                        */
        /* ------------------------------------------------------------------ */

        Route::controller(LearningAssessmentController::class)->prefix('learning-assessment')->group(function () {
            Route::get('/meta', 'meta');
            Route::get('/students', 'students');
            Route::get('/recent', 'recent');
            Route::post('/scores', 'store');
            Route::get('/export', 'export');
            Route::post('/import-analyze/export', 'importAnalyzeExport');
            Route::post('/import-analyze', 'importAnalyze');
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
