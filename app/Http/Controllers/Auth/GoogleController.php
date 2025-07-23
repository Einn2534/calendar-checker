<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;

class GoogleController extends Controller
{
    /**
     * Redirect the user to Google's OAuth consent screen.
     */
    public function redirect(GoogleCalendarService $gcal)
    {
        return redirect()->away($gcal->getAuthUrl());
    }

    /**
     * Handle OAuth callback from Google.
     */
    public function callback(Request $request, GoogleCalendarService $gcal)
    {
        $code = $request->input('code');
        if ($code) {
            $gcal->handleCallback($code);
        }

        return redirect('/availability');
    }
}
