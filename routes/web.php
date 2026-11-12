<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// SPA-фоллбек: любые пути (кроме /api) отдают точку входа Vue-роутера.
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '^(?!api).*$');
