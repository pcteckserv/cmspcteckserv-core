@extends('cms-core::customers.layout')
@section('content')<h1>Olá, {{ $customer->name }}</h1>
@include('cms-core::customers.history')
<section><h2>O meu perfil</h2><p>{{ $customer->email }}</p><form method="post" action="{{ route('customers.profile.update') }}">@csrf @method('PUT')
<div class="grid">@foreach(['name'=>'Nome','company'=>'Empresa','phone'=>'Telefone','vat'=>'Contribuinte'] as $field=>$label)<label>{{ $label }}<input name="{{ $field }}" value="{{ old($field,$customer->$field) }}" @if($field==='name') required @endif></label>@endforeach</div>
<label>Palavra-passe atual<input type="password" name="current_password" autocomplete="current-password" required></label><label>Nova palavra-passe (opcional)<input type="password" name="password" autocomplete="new-password" minlength="12"></label><label>Confirmar nova palavra-passe<input type="password" name="password_confirmation" autocomplete="new-password"></label><button>Guardar perfil</button></form></section>
<section><h2>As minhas moradas</h2><div class="grid">@forelse($customer->addresses as $address)<div><strong>{{ $address->label }}</strong><p>{{ implode(', ', array_filter($address->details)) }}</p></div>@empty<p>Ainda não tem moradas guardadas.</p>@endforelse</div><a href="{{ route('customers.addresses') }}">Gerir moradas</a></section>
@endsection
