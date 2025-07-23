<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
// OAuth 認可
Route::get('/oauth2callback', [App\Http\Controllers\Auth\GoogleController::class, 'callback']);
// 空き時間表示
Route::get('/availability', [App\Http\Controllers\AvailabilityController::class, 'index']);

Route::get('/', function () {
    return view('welcome');
});
