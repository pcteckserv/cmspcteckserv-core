@extends('cms-core::customers.layout', ['title'=>'O meu perfil'])
@section('content')
<h1>O meu perfil</h1><section><p>{{ $customer->email }}</p>
<form autocomplete="off" method="post" action="{{ route('customers.profile.update') }}">@csrf<input type="hidden" name="account_form_controlled" value="1"> @method('PUT')
<div class="grid">@foreach(['name'=>'Nome','company'=>'Empresa','phone'=>'Telefone','vat'=>'Contribuinte'] as $field=>$label)<label>{{ $label }}<input name="{{ $field }}" value="{{ old($field,$customer->$field) }}" @if($field==='name') required @endif></label>@endforeach</div>
<label>Palavra-passe atual<input type="password" name="current_password" autocomplete="off" required></label>
<details><summary>Alterar palavra-passe</summary><label><input type="checkbox" name="change_password" value="1" data-enable-password-change> Definir uma nova palavra-passe</label><label>Nova palavra-passe<input type="password" name="password" autocomplete="new-password" data-new-password disabled minlength="12"></label><label>Confirmar nova palavra-passe<input type="password" name="password_confirmation" autocomplete="new-password" data-new-password disabled></label></details>
<button>Guardar perfil</button></form></section>
<script src="{{ route('cms-core.account-form-script',['v'=>'2.18.0']) }}" defer></script>
@endsection
