@extends('cms-core::emails.layout')
@section('email-content')
<h1 class="cms-email__title" style="font-size:24px;line-height:1.3;margin:0 0 24px">{{ $email['heading'] ?? $email['subject'] }}</h1>
@foreach(explode("\n", $email['body'] ?? '') as $paragraph)
<p class="cms-email__paragraph">{{ $paragraph }}</p>
@endforeach
@if(!empty($email['action_url']))
<p><a class="cms-email__action" href="{{ $email['action_url'] }}" style="color:#111111;font-weight:bold">{{ $email['action_label'] ?? 'Abrir ligação' }}</a></p>
@endif
@endsection
