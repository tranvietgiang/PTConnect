<?php

use App\Http\Controllers\Api\AcademicYearController;
use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\AssignmentSubmissionController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClassroomController;
use App\Http\Controllers\Api\EmailNotificationController;
use App\Http\Controllers\Api\ScoreColumnController;
use App\Http\Controllers\Api\ScoreController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/refresh', [AuthController::class, 'refresh']);

Route::middleware('auth:api')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/users', [UserController::class, 'index']);

    Route::get('/academic-years', [AcademicYearController::class, 'index']);

    Route::get('/classes', [ClassroomController::class, 'index']);
    Route::get('/classes/{id}', [ClassroomController::class, 'show']);
    Route::post('/classes', [ClassroomController::class, 'store']);
    Route::put('/classes/{id}', [ClassroomController::class, 'update']);
    Route::delete('/classes/{id}', [ClassroomController::class, 'destroy']);

    Route::get('/students', [StudentController::class, 'index']);
    Route::get('/students/{id}', [StudentController::class, 'show']);
    Route::post('/students', [StudentController::class, 'store']);
    Route::post('/students/import', [StudentController::class, 'import']);

    Route::get('/attendance/today', [AttendanceController::class, 'today']);
    Route::get('/attendance/history', [AttendanceController::class, 'history']);
    Route::post('/attendance', [AttendanceController::class, 'submit']);
    Route::get('/attendance/student', [AttendanceController::class, 'studentHistory']);
    Route::get('/attendance/sessions', [AttendanceController::class, 'sessions']);
    Route::post('/attendance/sessions', [AttendanceController::class, 'storeSession']);
    Route::post('/attendance/sessions/bulk', [AttendanceController::class, 'storeSessionsBulk']);
    Route::get('/attendance/sessions/{id}', [AttendanceController::class, 'showSession']);
    Route::put('/attendance/sessions/{id}', [AttendanceController::class, 'updateSession']);
    Route::delete('/attendance/sessions/{id}', [AttendanceController::class, 'destroySession']);
    Route::post('/attendance/sessions/{id}/close', [AttendanceController::class, 'closeSession']);

    Route::get('/scores', [ScoreController::class, 'index']);
    Route::get('/scores/report', [ScoreController::class, 'report']);
    Route::get('/score-columns', [ScoreColumnController::class, 'index']);
    Route::post('/score-columns', [ScoreColumnController::class, 'store']);
    Route::put('/score-columns/{id}', [ScoreColumnController::class, 'update']);
    Route::post('/score-records', [ScoreController::class, 'saveRecords']);

    Route::get('/assignments', [AssignmentController::class, 'index']);
    Route::post('/assignments', [AssignmentController::class, 'store']);
    Route::get('/assignments/{id}/attachment', [AssignmentController::class, 'downloadAttachment']);
    Route::post('/assignments/{id}/submissions', [AssignmentSubmissionController::class, 'store']);
    Route::get('/assignment-submissions/{id}/download', [AssignmentSubmissionController::class, 'download']);
    Route::patch('/assignment-submissions/{id}/grade', [AssignmentSubmissionController::class, 'grade']);

    Route::get('/email-notifications', [EmailNotificationController::class, 'index']);
    Route::post('/email-notifications', [EmailNotificationController::class, 'send']);
});
