@extends('layouts.app')

@section('content')
<h1>空き時間帯（同時予定数＜4）</h1>
<ul>
    @foreach($availabilities as $slot)
    <li>
        {{ $slot['start']->format('Y/m/d H:i') }}
        〜
        {{ $slot['end']->format('Y/m/d H:i') }}
    </li>
    @endforeach
</ul>
@endsection
