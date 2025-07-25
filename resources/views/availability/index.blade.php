@extends('layouts.app')

@section('content')
<h1>空き時間帯（同時予定数＜4）</h1>

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
$timeRange = $slot['start']->format('H:i') . '~' . $slot['end']->format('H:i');
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