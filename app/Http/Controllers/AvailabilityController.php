<?php

namespace App\Http\Controllers;

use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function index(Request $request, GoogleCalendarService $gcal)
    {
        // AvailabilityController@index の先頭でチェック
        if (!session()->has('google_access_token')) {
            return redirect()->route('google.auth');
        }

        try {
            // 水曜10:00 〜 一か月後20:30 を検索範囲に
            $from = Carbon::now()->startOfWeek()->addDays(2)->setTime(10, 0);
            $to = $from->copy()->addMonth()->setTime(20, 30);
            $period = CarbonPeriod::create($from, '30 minutes', $to);


            $calendarIds = [
                'c_b31c94dc62779426bd55fdf4977b90cc0ef16a5b20ba155020827586bc17f1f0@group.calendar.google.com',
                'c_d636f481e9e8c0673d8e9afa328a8d06e4cd60f7438df702c79cf197d85fd907@group.calendar.google.com',
                'c_80af6e1d5b321848980325495f5d69599b2d26b044a3e7f8ec2f72b726d901f4@group.calendar.google.com',
                'c_8ac9fbda61cc548ae6e004c3d7337eab2528c95736ff885896114c8236d0222f@group.calendar.google.com',
                'c_classroom23e999f9@group.calendar.google.com',
                'c_2888b1ae9946b6d814887346029fdd74875b4b98b67f1f90ba80b1b6d1f43b1d@group.calendar.google.com',
                'c_dcc1067352f7ccb0bf8b4a89d33fbd5ff44f4697e3aad372c710881e165e727c@group.calendar.google.com',
                'c_62fdd6187530c4c29548c8a4e7ebf51cff6306f65b92da7da403a2009635e068@group.calendar.google.com',
            ];

            $events = $gcal->fetchEvents($calendarIds, $from, $to);

            $calendarNames = [];
            $points = [];

            foreach ($calendarIds as $calId) {
                try {
                    $calendar = $gcal->getCalendar($calId); // カレンダー名だけ取得する関数を追加
                    $calendarName = $calendar->getSummary();
                    $calendarNames[] = $calendarName;
                } catch (\Throwable $e) {
                    $calendarNames[] = "[取得失敗: {$calId}]";
                }

                // イベント取得
                $calendarEvents = $gcal->fetchEvents([$calId], $from, $to);

                // 「どれだけ件数があっても 2 件として扱う」ロジック
                $specialCalendarId = 'c_62fdd6187530c4c29548c8a4e7ebf51cff6306f65b92da7da403a2009635e068@group.calendar.google.com';
                if ($calId === $specialCalendarId) {
                    // 自身を連結 → 件数が1なら2件に、2件以上なら2件に、0件なら0件のまま
                    $calendarEvents = array_slice(
                        array_merge($calendarEvents, $calendarEvents),
                        0,
                        2
                    );
                }

                foreach ($calendarEvents as $item) {
                    $ev    = $item['event'];
                    $start = new Carbon($ev->getStart()->getDateTime() ?? $ev->getStart()->getDate());
                    $end   = new Carbon($ev->getEnd()->getDateTime()   ?? $ev->getEnd()->getDate());

                    if (in_array($start->dayOfWeekIso, [1, 2])) continue;

                    // 1イベントが+1/-1 なので、同じものが2件あれば+2/-2 に
                    $points[] = ['time' => $start, 'delta' => +1];
                    $points[] = ['time' => $end,   'delta' => -1];
                }
            }

            if (empty($points)) {
                $availabilities[] = ['start' => $from->copy(), 'end' => $to->copy()];
            }

            usort($points, fn($a, $b) => $a['time']->lt($b['time']) ? -1 : 1);

            $count = 0;
            $availabilities = [];
            $windowStart = $from;

            foreach ($points as $pt) {
                $now = $pt['time'];
                if ($count < 4 && $now->gt($windowStart)) {
                    $availabilities[] = ['start' => $windowStart->copy(), 'end' => $now->copy()];
                }
                $count += $pt['delta'];
                $windowStart = $now;
            }

            if ($count < 4 && $windowStart->lt($to)) {
                $availabilities[] = ['start' => $windowStart, 'end' => $to];
            }
            $availabilities = array_filter($availabilities, function ($slot) {
                $start = $slot['start'];
                $end = $slot['end'];

                // 曜日番号取得（ISO：月=1〜日=7）
                $day = $start->dayOfWeekIso;

                // 月・火を除外
                if (in_array($day, [1, 2])) return false;

                // 日をまたぐ枠は除外
                if (!$start->isSameDay($end)) return false;

                // 時間帯を曜日別に設定
                if (in_array($day, [6, 7])) {
                    // 土日：10:00〜18:30
                    $dayStart = $start->copy()->setTime(10, 0);
                    $dayEnd = $start->copy()->setTime(18, 30);
                } else {
                    // 水〜金：10:00〜20:30
                    $dayStart = $start->copy()->setTime(10, 0);
                    $dayEnd = $start->copy()->setTime(20, 30);
                }

                // 除外：12:00〜14:00
                $excludeStart = $start->copy()->setTime(12, 0);
                $excludeEnd = $start->copy()->setTime(14, 0);

                return
                    $start->betweenIncluded($dayStart, $dayEnd) &&
                    $end->betweenIncluded($dayStart, $dayEnd) &&
                    (
                        $end->lte($excludeStart) || $start->gte($excludeEnd)
                    );
            });


            // 空き時間のマージ（連続している時間帯を1つにまとめる）
            $merged = [];
            usort($availabilities, fn($a, $b) => $a['start']->lt($b['start']) ? -1 : 1);

            foreach ($availabilities as $slot) {
                if (empty($merged)) {
                    $merged[] = $slot;
                    continue;
                }

                $last = &$merged[count($merged) - 1];

                // 連続または隣接（例：18:00 → 18:30）していればマージ
                if ($last['end']->equalTo($slot['start']) || $last['end']->diffInMinutes($slot['start']) <= 5) {
                    $last['end'] = $slot['end'];
                } else {
                    $merged[] = $slot;
                }
            }

            $availabilities = $merged;

            $calendarNames = array_unique($calendarNames);

            return view('availability.index', compact('availabilities', 'calendarNames'));
        } catch (\Throwable $e) {
            return view('availability.error', ['message' => $e->getMessage()]);
        }
    }
}
