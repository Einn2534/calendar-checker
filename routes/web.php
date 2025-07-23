<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AvailabilityController;
use App\Services\GoogleCalendarService;

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

Route::get('/auth/google', function (GoogleCalendarService $gcal) {
    return redirect($gcal->getAuthUrl());
})->name('google.auth');

Route::get('/auth/google/callback', function (\Illuminate\Http\Request $request, GoogleCalendarService $gcal) {
    if ($request->has('code')) {
        $gcal->handleCallback($request->get('code'));
        return redirect('/availability'); // ← 認可後に空き時間ページへ戻す
    } else {
        return 'Google認証に失敗しました';
    }
})->name('google.callback');
// 空き時間表示
Route::get('/availability', [AvailabilityController::class, 'index']);

Route::get('/', function () {
    return view('welcome');
});
