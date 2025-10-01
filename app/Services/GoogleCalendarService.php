<?php

namespace App\Services;

use Carbon\Carbon;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use RuntimeException;

class GoogleCalendarService
{
    protected Google_Client $client;
    protected Google_Service_Calendar $service;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setAuthConfig(config('google.client_credentials'));
        $this->client->setRedirectUri(config('google.redirect_uri'));
        foreach (config('google.scopes', []) as $scope) {
            $this->client->addScope($scope);
        }
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
    public function handleCallback(string $code): void
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);
        if (isset($token['error'])) {
            throw new RuntimeException('Google OAuth error: ' . $token['error']);
        }
        session(['google_access_token' => $token]);
        $this->client->setAccessToken($token);
    }

    /** セッションのトークンをセット */
    protected function ensureAccessToken(): void
    {
        $token = session('google_access_token');
        if (!$token) {
            throw new RuntimeException('Google トークンがありません。');
        }
        $this->client->setAccessToken($token);
        // 必要ならリフレッシュ
        if ($this->client->isAccessTokenExpired()) {
            $refreshToken = $token['refresh_token'] ?? null;
            if (!$refreshToken) {
                throw new RuntimeException('Google リフレッシュトークンがありません。');
            }

            $newToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
            if (isset($newToken['error'])) {
                throw new RuntimeException('Google トークンの更新に失敗しました: ' . $newToken['error']);
            }

            $token = array_merge($token, $newToken);
            $this->client->setAccessToken($token);
            session(['google_access_token' => $token]);
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

        usort($allEvents, function (Google_Service_Calendar_Event $a, Google_Service_Calendar_Event $b): int {
            $startA = $a->getStart()->getDateTime() ?? $a->getStart()->getDate();
            $startB = $b->getStart()->getDateTime() ?? $b->getStart()->getDate();

            return strcmp($startA, $startB);
        });

        return $allEvents;
    }
}
