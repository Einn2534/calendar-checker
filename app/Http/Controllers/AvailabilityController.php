<?php

namespace App\Http\Controllers;

use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function index(Request $request, GoogleCalendarService $gcal)
    {
        try {
        // 例：今週の月曜 9:00 ～ 金曜 18:00 を検索期間に
        $from = Carbon::now()->startOfWeek()->addHours(9);
        $to   = Carbon::now()->endOfWeek()->setHour(18);

        // 取得したいカレンダーIDを列挙（env や DB から動的に）
        $calendarIds = [
            'c_b31c94dc62779426bd55fdf4977b90cc0ef16a5b20ba155020827586bc17f1f0@group.calendar.google.com',
            'c_d636f481e9e8c0673d8e9afa328a8d06e4cd60f7438df702c79cf197d85fd907@group.calendar.google.com',
            'c_80af6e1d5b321848980325495f5d69599b2d26b044a3e7f8ec2f72b726d901f4@group.calendar.google.com',
            'c_8ac9fbda61cc548ae6e004c3d7337eab2528c95736ff885896114c8236d0222f@group.calendar.google.com',
            'c_classroom23e999f9@group.calendar.google.com',
            'c_8ac9fbda61cc548ae6e004c3d7337eab2528c95736ff885896114c8236d0222f@group.calendar.google.com',
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
        } catch (\Throwable $e) {
            return view('availability.error', ['message' => $e->getMessage()]);
        }
    }
}
