@extends('layouts.app')

@section('content')
<h1>空き時間帯（同時予定数＜4）</h1>
<h2>使用カレンダー</h2>
<ul>
    @foreach(array_unique($calendarNames) as $name)
    <li>{{ $name }}</li>
    @endforeach
</ul>


<ul>
    @foreach($availabilities as $slot)
    <li>
        {{ $slot['start']->format('m/d H:i') }}
        〜
        {{ $slot['end']->format('m/d H:i') }}
    </li>
    @endforeach
</ul>
@endsection