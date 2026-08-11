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

/*
|--------------------------------------------------------------------------
| Auth Routes (Public)
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [SignupController::class, 'store']);
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/update-password', [UserController::class, 'updatePassword']);

    Route::apiResource('users', UserController::class);

    // Tickets
    Route::apiResource('/tickets', TicketController::class);
    Route::get('/tickets/{ticket}/attachment/{type}', [TicketController::class, 'attachment']);
    Route::get('/getTickets', [TicketController::class, 'getTickets']);
    Route::get('/getOpenTickets', [TicketController::class, 'getOpenTickets']);
    Route::middleware('role:ADMIN')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/logs', [LogController::class, 'index']);
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
