<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SignupController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\ProblemCategoryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\ReportController;

/*
|--------------------------------------------------------------------------
| Auth Routes (Public)
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [SignupController::class, 'store']);
Route::get('/attachments/{id}', [TicketController::class, 'viewAttachment']);

Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/update-password', [UserController::class, 'updatePassword']);

    Route::apiResource('users', UserController::class);

    // Organization Structure Routes
    Route::apiResource('divisions', DivisionController::class);
    Route::apiResource('sections', SectionController::class);

    // Tickets & Attachments
    Route::apiResource('/tickets', TicketController::class);
    Route::post('/tickets/{ticket}/rating', [TicketController::class, 'submitRating']);
    Route::post('/tickets/{ticket}/assign-self', [TicketController::class, 'assignSelf']);
    Route::post('/tickets/{ticket}/resolve', [TicketController::class, 'resolveTicket']);
    Route::get('/tickets/{ticket}/attachment/{type}', [TicketController::class, 'attachment']);
    Route::get('/attachments/{attachment}', [TicketController::class, 'viewAttachment']);
    Route::get('/getTickets', [TicketController::class, 'getTickets']);
    Route::get('/getOpenTickets', [TicketController::class, 'getOpenTickets']);
    Route::get('/logs', [LogController::class, 'index']);

    // Reports & Analytics
    Route::get('/reports/analytics', [ReportController::class, 'analytics']);

    Route::middleware('role:ADMIN')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
    });

    Route::prefix('problem-categories')->group(function () {
        Route::get('/', [ProblemCategoryController::class, 'getAll']);
        Route::post('/', [ProblemCategoryController::class, 'store']);
        Route::get('/type/{type}', [ProblemCategoryController::class, 'byType']);
        Route::get('/type/{type}/count', [ProblemCategoryController::class, 'getCount']);
        Route::get('/{id}', [ProblemCategoryController::class, 'show']);          // GET    /api/problem-categories/{id}
        Route::put('/{id}', [ProblemCategoryController::class, 'update']);
        Route::patch('/{id}', [ProblemCategoryController::class, 'update']);
        Route::delete('/{id}', [ProblemCategoryController::class, 'destroy']);    // DELETE /api/problem-categories/{id}
    });
});

/*
|--------------------------------------------------------------------------
| Health Check
|--------------------------------------------------------------------------
*/
Route::get('/ping', function () {
    return response()->json(['message' => 'this is api']);
});
