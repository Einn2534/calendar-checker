{{-- resources/views/availability/error.blade.php --}}
@extends('layouts.app')

@section('content')
<h1>エラーが発生しました</h1>
<p>{{ $message }}</p> {{-- ← これが正しい書き方 --}}
@endsection