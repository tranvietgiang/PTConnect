<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\ClassroomController;
use App\Http\Controllers\Api\ScoreController;
use App\Http\Controllers\Api\StudentController;
use Illuminate\Support\Facades\Route;

Route::get('/me', [AuthController::class, 'me']);

Route::prefix('auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});

Route::middleware('jwt.auth')->group(function (): void {
    Route::get('/classes', [ClassroomController::class, 'index']);
    Route::get('/classes/{classroom}', [ClassroomController::class, 'show']);
    Route::post('/classes', [ClassroomController::class, 'store'])->middleware('role:system_admin,school_admin');
    Route::put('/classes/{classroom}', [ClassroomController::class, 'update'])->middleware('role:system_admin,school_admin');
    Route::patch('/classes/{classroom}', [ClassroomController::class, 'update'])->middleware('role:system_admin,school_admin');

    Route::get('/students', [StudentController::class, 'index']);
    Route::post('/students', [StudentController::class, 'store'])->middleware('role:system_admin,school_admin,teacher');
    Route::post('/students/import', [StudentController::class, 'import'])->middleware('role:system_admin,school_admin');
    Route::put('/students/{student}', [StudentController::class, 'update'])->middleware('role:system_admin,school_admin,teacher');
    Route::get('/students/{student}', [StudentController::class, 'show']);

    Route::get('/attendance/sessions', [AttendanceController::class, 'sessions'])->middleware('role:system_admin,school_admin,teacher,assistant');
    Route::post('/attendance/sessions', [AttendanceController::class, 'storeSession'])->middleware('role:system_admin,school_admin,teacher');
    Route::post('/attendance/sessions/bulk', [AttendanceController::class, 'bulkStoreSessions'])->middleware('role:system_admin,school_admin,teacher');
    Route::get('/attendance/sessions/{session}', [AttendanceController::class, 'showSession'])->middleware('role:system_admin,school_admin,teacher,assistant');
    Route::put('/attendance/sessions/{session}', [AttendanceController::class, 'updateSession'])->middleware('role:system_admin,school_admin,teacher');
    Route::patch('/attendance/sessions/{session}/close', [AttendanceController::class, 'closeSession'])->middleware('role:system_admin,school_admin,teacher');
    Route::delete('/attendance/sessions/{session}', [AttendanceController::class, 'destroySession'])->middleware('role:system_admin,school_admin,teacher');
    Route::get('/attendance/today', [AttendanceController::class, 'today'])->middleware('role:system_admin,school_admin,teacher,assistant');
    Route::post('/attendance', [AttendanceController::class, 'store'])->middleware('role:system_admin,school_admin,teacher,assistant');
    Route::post('/attendance/sessions/{session}/send-email', [AttendanceController::class, 'sendEmail'])->middleware('role:system_admin,school_admin,teacher,assistant');
    Route::get('/attendance/history', [AttendanceController::class, 'history'])->middleware('role:system_admin,school_admin,teacher,assistant');
    Route::get('/attendance/parent', [AttendanceController::class, 'parentHistory'])->middleware('role:student');

    Route::get('/assignments', [AssignmentController::class, 'index']);
    Route::post('/assignments', [AssignmentController::class, 'store'])->middleware('role:system_admin,school_admin,teacher');
    Route::get('/assignments/{assignment}/attachment', [AssignmentController::class, 'downloadAttachment']);
    Route::post('/assignments/{assignment}/submissions', [AssignmentController::class, 'submit'])->middleware('role:student');
    Route::patch('/assignment-submissions/{submission}/grade', [AssignmentController::class, 'gradeSubmission'])->middleware('role:system_admin,school_admin,teacher,assistant');
    Route::post('/assignment-submissions/{submission}/send-email', [AssignmentController::class, 'sendScoreEmail'])->middleware('role:system_admin,school_admin,teacher,assistant');
    Route::post('/assignment-submissions/send-email-bulk', [AssignmentController::class, 'sendBulkScoreEmail'])->middleware('role:system_admin,school_admin,teacher,assistant');
    Route::get('/assignment-submissions/{submission}/download', [AssignmentController::class, 'downloadSubmission']);

    Route::get('/scores', [ScoreController::class, 'index'])->middleware('role:system_admin,school_admin,teacher,assistant,student');
    Route::get('/scores/report', [ScoreController::class, 'report'])->middleware('role:system_admin,school_admin,teacher,assistant');
});
