@extends('cms-core::customers.layout', ['title'=>'O meu perfil'])
@section('content')
<h1>O meu perfil</h1><section><p>{{ $customer->email }}</p>
<form method="post" action="{{ route('customers.profile.update') }}">@csrf @method('PUT')
<div class="grid">@foreach(['name'=>'Nome','company'=>'Empresa','phone'=>'Telefone','vat'=>'Contribuinte'] as $field=>$label)<label>{{ $label }}<input name="{{ $field }}" value="{{ old($field,$customer->$field) }}" @if($field==='name') required @endif></label>@endforeach</div>
<label>Palavra-passe atual<input type="password" name="current_password" autocomplete="current-password" required></label>
<details><summary>Alterar palavra-passe</summary><label>Nova palavra-passe<input type="password" name="password" autocomplete="new-password" minlength="12"></label><label>Confirmar nova palavra-passe<input type="password" name="password_confirmation" autocomplete="new-password"></label></details>
<button>Guardar perfil</button></form></section>
@endsection
