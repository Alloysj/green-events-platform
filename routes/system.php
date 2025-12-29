<?php

use Illuminate\Support\Facades\Route;

Route::get('/system/ping', function () {
    return response()->json(['status' => 'ok']);
});
