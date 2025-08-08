<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('chat');
});

Route::get('/chat', function () {
    return view('chat');
});
