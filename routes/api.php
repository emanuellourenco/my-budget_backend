<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TagsController;
use App\Http\Controllers\TransactionsController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post('/login', [AuthController::class, 'login']);
Route::post('/signup', [AuthController::class, 'signup']);
Route::post('/logout', [AuthController::class, 'logout']);

Route::apiResource('/user', UsersController::class);
Route::apiResource('/tags', TagsController::class);
Route::post('/tags/options', [TagsController::class, 'options']);
Route::apiResource('/transactions', TransactionsController::class);
