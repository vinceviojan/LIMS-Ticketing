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
use App\Http\Controllers\SystemSettingController;


use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
/*
|--------------------------------------------------------------------------
| Auth Routes (Public)
|--------------------------------------------------------------------------
*/

RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(40)->by($request->user()?->id ?: $request->ip());
});

Route::middleware('throttle:api')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [SignupController::class, 'store']);
    Route::get('/attachments/{id}', [TicketController::class, 'viewAttachment']);
    
    // Public Organization Routes
    Route::get('/divisions', [DivisionController::class, 'index']);
    Route::get('/sections', [SectionController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        Route::post('/update-password', [UserController::class, 'updatePassword']);

        Route::apiResource('users', UserController::class);

        // Organization Structure Routes
        Route::apiResource('divisions', DivisionController::class)->except(['index']);
        Route::apiResource('sections', SectionController::class)->except(['index']);

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
        Route::get('/logs/session', [LogController::class, 'getBySession']);

        // Reports & Analytics
        Route::get('/reports/analytics', [ReportController::class, 'analytics']);

        Route::middleware('role:ADMIN')->prefix('admin')->group(function () {
            Route::get('/dashboard', [AdminController::class, 'dashboard']);
            Route::get('/logs', [LogController::class, 'index']);
            Route::put('/update-admin-info', [AdminController::class, 'updateAdminInfo']);
            Route::get('/settings', [SystemSettingController::class, 'show']);
            Route::put('/settings', [SystemSettingController::class, 'update']);
        });

        Route::get('/getDivisionAndSection', [AdminController::class, 'getDivisionAndSection']);

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
});
