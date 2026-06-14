<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::get('/rules', function () {
    return view('rules');
});

Route::get('/vue-test', function () {
    return view('vue-test');
});
