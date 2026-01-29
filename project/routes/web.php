<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['message' => 'Hello']);
});

Route::fallback(function () {
    return response()->json(['message' => 'Not Found'], 404);
});
