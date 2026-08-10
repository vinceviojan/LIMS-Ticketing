<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\ProblemCategoryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LogController;

/*
|--------------------------------------------------------------------------
| Auth Routes (Public)
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);

/*
# Get all
curl -X GET http://localhost:8000/api/problem-categories

# Get by type
curl -X GET http://localhost:8000/api/problem-categories/type/hardware

    # Count by type
    curl -X GET http://localhost:8000/api/problem-categories/type/hardware/count

    # Get single
    curl -X GET http://localhost:8000/api/problem-categories/1

    # Create
    curl -X POST http://localhost:8000/api/problem-categories \
    -H "Content-Type: application/json" \
    -d '{"type":"hardware","categories":"Laptop Issue"}'

    # Update
    curl -X PUT http://localhost:8000/api/problem-categories/1 \
    -H "Content-Type: application/json" \
    -d '{"type":"software","categories":"OS Crash"}'

    # Delete
    curl -X DELETE http://localhost:8000/api/problem-categories/1
*/

/*
|--------------------------------------------------------------------------
| Protected Routes (Sanctum Token Required)
Invoke-RestMethod -Uri "http://localhost:8000/api/login" -Method POST -ContentType "application/json" -Body '{"email":"admin@lims.gov.ph","password":"password"}'
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::apiResource('users', UserController::class);

    // Tickets
    Route::apiResource('/tickets', TicketController::class);
    Route::get('/logs', [LogController::class, 'index']);

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
