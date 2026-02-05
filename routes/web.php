<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SearchController;

Route::get('/', [SearchController::class, 'index']);
Route::match(['get', 'post'], '/search', [SearchController::class, 'search']);
