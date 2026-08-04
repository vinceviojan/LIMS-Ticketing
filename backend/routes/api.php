<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use app\Http\Controllers\TicketController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Route::resource('/ticket', TicketController::Tickets);

Route::get('/ping', function () {
    return response()->json([
        'message' => 'pong'
        ]);
});
