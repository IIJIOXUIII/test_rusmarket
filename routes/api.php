<?php

use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::get('/bootstrap', [ScheduleController::class, 'bootstrap']);
Route::get('/schedule', [ScheduleController::class, 'index']);
Route::put('/schedule', [ScheduleController::class, 'update']);
