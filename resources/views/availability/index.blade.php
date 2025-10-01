@extends('layouts.app')

@section('title', '空き時間帯ビューア')

@section('content')
    <p>下のリストは「同時予定数が4件未満」の時間帯を表示します。</p>
    <p>
        <a class="button" href="{{ route('google.auth') }}">Google カレンダーを連携</a>
    </p>

    @if (empty($availabilities))
        <div class="empty-state">条件に合致する空き時間は見つかりませんでした。</div>
    @else
        <ul class="availabilities">
            @foreach($availabilities as $slot)
                <li>
                    <span>{{ $slot['start']->format('Y/m/d H:i') }}</span>
                    <span>〜</span>
                    <span>{{ $slot['end']->format('Y/m/d H:i') }}</span>
                </li>
            @endforeach
        </ul>
    @endif
@endsection
