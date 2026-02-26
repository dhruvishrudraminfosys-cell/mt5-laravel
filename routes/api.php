<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Mt5TickController;
use App\Http\Controllers\StripeController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/mt5/tick', function (Request $request) {

    return response()->json([
        'status' => 'ok',
        'symbol' => $request->input('symbol'),
        'bid'    => $request->input('bid'),
        'ask'    => $request->input('ask'),
        'time'   => $request->input('time'),
        'signal' => 'NONE'
    ]);
});

Route::post('/mt5/tick', [Mt5TickController::class, 'store']);


Route::get('/mt5/ticks', [Mt5TickController::class, 'index']);

Route::post('/stripe/deposit', [StripeController::class, 'createDeposit']);
Route::post('/stripe/withdraw', [StripeController::class, 'withdraw']);
Route::post('/stripe/webhook', [StripeController::class, 'webhook']);


