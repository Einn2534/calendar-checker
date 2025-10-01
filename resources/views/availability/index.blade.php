@extends('layouts.app')

@section('content')
<h1>空き時間帯（同時予定数＜{{ $maxConcurrent }}）</h1>

<div class="mb-3">
    <span>表示切り替え：</span>
    @foreach ($allowedConcurrentCaps as $cap)
        @php
            $isActive = $cap === $maxConcurrent;
            $query = array_merge(request()->query(), ['max_concurrent' => $cap]);
        @endphp
        <a
            href="{{ route('availability.index', $query) }}"
            class="btn {{ $isActive ? 'btn-primary' : 'btn-outline-primary' }}"
            style="margin-right: 0.5rem;"
        >
            {{ sprintf('同時予定数＜%d', $cap) }}
        </a>
    @endforeach
</div>

<h2>使用カレンダー</h2>
<ul>
    @foreach(array_unique($calendarNames) as $name)
    <li>{{ $name }}</li>
    @endforeach
</ul>

@php
setlocale(LC_TIME, 'ja_JP.UTF-8');
$slotsByDate = [];
foreach ($availabilities as $slot) {

$weekdays = ['日', '月', '火', '水', '木', '金', '土'];
$wday = $weekdays[$slot['start']->dayOfWeek]; // 0〜6
$dateLabel = $slot['start']->format('n月j日') . "（{$wday}）";
$busyCount = $slot['busy'] ?? 0;
$availableSlots = max(0, $maxConcurrent - $busyCount);
$timeRange = sprintf(
    '%s~%s（予定%d件／残り%d枠）',
    $slot['start']->format('H:i'),
    $slot['end']->format('H:i'),
    $busyCount,
    $availableSlots
);
$slotsByDate[$dateLabel][] = $timeRange;
}
@endphp


<h2>空き時間一覧</h2>
<ul>
    @foreach ($slotsByDate as $dateLabel => $times)
        <li>{{ $dateLabel }}：{{ implode('、', $times) }}</li>
    @endforeach
</ul>
@endsection
