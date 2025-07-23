<?php

namespace App\Services;

use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Carbon\Carbon;

class GoogleCalendarService
{
    protected Google_Client $client;
    protected Google_Service_Calendar $service;

    public function __construct()
    {
        $this->client = new Google_Client();
        $credPath = config('google.client_credentials');
        if (!is_string($credPath) || !file_exists($credPath)) {
            throw new \RuntimeException('Google credential file not found.');
        }
        $this->client->setAuthConfig($credPath);
        $this->client->setRedirectUri(config('google.redirect_uri'));
        $this->client->addScope(Google_Service_Calendar::CALENDAR_READONLY);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');

        $this->service = new Google_Service_Calendar($this->client);
    }

    /** 認可用 URL を返す */
    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    /** OAuth コールバック後にトークンを保存 */
    public function handleCallback(string $code)
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);
        session(['google_access_token' => $token]);
        $this->client->setAccessToken($token);
    }

    /** セッションのトークンをセット */
    protected function ensureAccessToken()
    {
        $token = session('google_access_token');
        if (!$token) {
            throw new \Exception('Google トークンがありません。');
        }
        $this->client->setAccessToken($token);
        // 必要ならリフレッシュ
        if ($this->client->isAccessTokenExpired()) {
            $this->client->fetchAccessTokenWithRefreshToken($token['refresh_token']);
            session(['google_access_token' => $this->client->getAccessToken()]);
        }
    }

    /**
     * 複数カレンダーから指定期間のイベントを取得
     * @param array $calendarIds
     * @param Carbon $from
     * @param Carbon $to
     * @return Google_Service_Calendar_Event[]
     */
    public function fetchEvents(array $calendarIds, Carbon $from, Carbon $to): array
    {
        $this->ensureAccessToken();
        $allEvents = [];

        foreach ($calendarIds as $calId) {
            $optParams = [
                'timeMin'      => $from->toRfc3339String(),
                'timeMax'      => $to->toRfc3339String(),
                'singleEvents' => true,
                'orderBy'      => 'startTime',
            ];
            $events = $this->service
                ->events
                ->listEvents($calId, $optParams)
                ->getItems();

            $allEvents = array_merge($allEvents, $events);
        }

        return $allEvents;
    }
}
