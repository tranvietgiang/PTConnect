<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\ClassroomController;
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
    Route::post('/classes', [ClassroomController::class, 'store'])->middleware('role:admin');

    Route::get('/students', [StudentController::class, 'index']);
    Route::post('/students', [StudentController::class, 'store'])->middleware('role:admin,teacher,assistant');
    Route::post('/students/import', [StudentController::class, 'import'])->middleware('role:admin,teacher,assistant');
    Route::get('/students/{student}', [StudentController::class, 'show']);

    Route::get('/assignments', [AssignmentController::class, 'index']);
    Route::post('/assignments', [AssignmentController::class, 'store'])->middleware('role:admin,teacher');
    Route::get('/assignments/{assignment}/attachment', [AssignmentController::class, 'downloadAttachment']);
    Route::post('/assignments/{assignment}/submissions', [AssignmentController::class, 'submit'])->middleware('role:parent');
    Route::get('/assignment-submissions/{submission}/download', [AssignmentController::class, 'downloadSubmission']);
});
