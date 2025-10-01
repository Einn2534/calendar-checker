<?php

namespace App\Http\Controllers;

use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Google_Service_Calendar_Event;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    private const BUSY_THRESHOLD = 4;

    public function index(Request $request, GoogleCalendarService $gcal)
    {
        // 例：今週の月曜 9:00 ～ 金曜 18:00 を検索期間に
        $from = Carbon::now()->startOfWeek()->addHours(9);
        $to   = Carbon::now()->endOfWeek()->setHour(18);

        // 取得したいカレンダーIDを列挙（env や DB から動的に）
        $calendarIds = [
            'primary',
            'team1@example.com',
            'team2@example.com',
            // ...
        ];

        $events = $gcal->fetchEvents($calendarIds, $from, $to);

        $points = [];
        foreach ($events as $event) {
            [$start, $end] = $this->resolveEventRange($event, $from, $to);
            if (!$start || !$end || $start->gte($end)) {
                continue;
            }

            $points[] = ['time' => $start, 'delta' => +1];
            $points[] = ['time' => $end,   'delta' => -1];
        }

        usort($points, function (array $a, array $b): int {
            $cmp = $a['time']->getTimestamp() <=> $b['time']->getTimestamp();
            if ($cmp !== 0) {
                return $cmp;
            }

            return $a['delta'] <=> $b['delta'];
        });

        $count = 0;
        $availabilities = [];
        $windowStart = $from->copy();

        foreach ($points as $pt) {
            $now = $pt['time'];
            if ($count < self::BUSY_THRESHOLD && $now->gt($windowStart)) {
                $this->pushAvailability($availabilities, $windowStart, $now);
            }

            $count += $pt['delta'];
            $windowStart = $now->copy();
        }

        if ($count < self::BUSY_THRESHOLD && $windowStart->lt($to)) {
            $this->pushAvailability($availabilities, $windowStart, $to);
        }

        return view('availability.index', compact('availabilities'));
    }

    /**
     * @return array{0: Carbon|null, 1: Carbon|null}
     */
    private function resolveEventRange(Google_Service_Calendar_Event $event, Carbon $from, Carbon $to): array
    {
        $start = $event->getStart()->getDateTime() ?? $event->getStart()->getDate();
        $end = $event->getEnd()->getDateTime() ?? $event->getEnd()->getDate();

        if (!$start || !$end) {
            return [null, null];
        }

        $startAt = Carbon::parse($start);
        $endAt = Carbon::parse($end);

        if ($endAt->lte($from) || $startAt->gte($to)) {
            return [null, null];
        }

        $startAt = $startAt->lt($from) ? $from->copy() : $startAt;
        $endAt = $endAt->gt($to) ? $to->copy() : $endAt;

        return [$startAt, $endAt];
    }

    private function pushAvailability(array &$availabilities, Carbon $start, Carbon $end): void
    {
        if ($start->gte($end)) {
            return;
        }

        if (!empty($availabilities)) {
            $lastIndex = array_key_last($availabilities);
            $last = $availabilities[$lastIndex];

            if ($last['end']->equalTo($start)) {
                $availabilities[$lastIndex]['end'] = $end->copy();
                return;
            }
        }

        $availabilities[] = ['start' => $start->copy(), 'end' => $end->copy()];
    }
}
