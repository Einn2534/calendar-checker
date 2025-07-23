<?php

namespace App\Http\Controllers;

use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
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

        // Sweep-line アルゴリズムで重複数をカウント
        $points = [];
        foreach ($events as $ev) {
            $start = new Carbon($ev->getStart()->getDateTime() ?? $ev->getStart()->getDate());
            $end   = new Carbon($ev->getEnd()->getDateTime() ?? $ev->getEnd()->getDate());
            $points[] = ['time' => $start, 'delta' => +1];
            $points[] = ['time' => $end,   'delta' => -1];
        }
        // 増分・減分でソート
        usort($points, fn($a, $b) => $a['time']->lt($b['time']) ? -1 : 1);

        $count = 0;
        $availabilities = [];
        $windowStart = $from;

        foreach ($points as $pt) {
            $now = $pt['time'];
            // “4つ以上” に到達する前の空き区間を記録
            if ($count < 4 && $now->gt($windowStart)) {
                $availabilities[] = ['start' => $windowStart->copy(), 'end' => $now->copy()];
            }
            $count += $pt['delta'];
            $windowStart = $now;
        }
        // 最後に to まで空きがあれば
        if ($count < 4 && $windowStart->lt($to)) {
            $availabilities[] = ['start' => $windowStart, 'end' => $to];
        }

        return view('availability.index', compact('availabilities'));
    }
}
