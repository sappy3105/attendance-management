@extends('layouts.default')

@section('content')
<div class="attendance__content">
    <div class="attendance__panel">
        <p>お疲れ様です！{{ Auth::user()->name }}さん</p>
    </div>
</div>
@endsection
