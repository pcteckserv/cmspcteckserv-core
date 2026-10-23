@extends('cms-core::customers.layout')
@section('content')<h1>Olá, {{ $customer->name }}</h1>
@include('cms-core::customers.history')
<section><h2>O meu perfil</h2><p>{{ $customer->name }} · {{ $customer->email }}</p><a href="{{ route('customers.profile') }}">Editar perfil</a></section>
<section><h2>As minhas moradas</h2><div class="grid">@forelse($customer->addresses as $address)<div><strong>{{ $address->label }}</strong><p>{{ implode(', ', array_filter($address->details)) }}</p></div>@empty<p>Ainda não tem moradas guardadas.</p>@endforelse</div><a href="{{ route('customers.addresses') }}">Gerir moradas</a></section>
@endsection
