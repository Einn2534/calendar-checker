<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\GoogleCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GoogleController extends Controller
{
    public function redirect(GoogleCalendarService $calendarService): RedirectResponse
    {
        return redirect()->away($calendarService->getAuthUrl());
    }

    public function callback(Request $request, GoogleCalendarService $calendarService): RedirectResponse
    {
        $code = $request->input('code');
        abort_unless(is_string($code) && $code !== '', 400, 'Google 認可コードが取得できませんでした。');

        $calendarService->handleCallback($code);

        return redirect()->route('availability.index');
    }
}
