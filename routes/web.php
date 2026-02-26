<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/success', function () {
    return "Payment Successful ✅";
});

Route::get('/cancel', function () {
    return "Payment Cancelled ❌";
});