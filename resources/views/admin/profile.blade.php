@extends('cms-core::admin.layouts.app', ['title'=>'O meu perfil'])
@section('content')
<div style="max-width:760px"><h1 class="h3 mb-3">O meu perfil</h1>
@if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<p class="mb-1">{{ $error }}</p>@endforeach</div>@endif
<section class="card p-4"><form autocomplete="off" method="post" enctype="multipart/form-data" action="{{ route('admin.profile.update') }}" class="vstack gap-3">@csrf<input type="hidden" name="account_form_controlled" value="1"> @method('PUT')
@include('cms-core::admin.partials.staff-profile')
<label>Nome<input class="form-control" name="name" value="{{ old('name',$user->name) }}" autocomplete="name" required maxlength="190"></label>
<label><input type="checkbox" name="change_email" value="1" data-enable-email-change> Alterar email</label><label>Email<input class="form-control" type="email" name="email" @if(isset($user) && $user) readonly data-edit-email @endif autocomplete="off" data-lpignore="true" data-1p-ignore="true" value="{{ old('email',$user->email) }}"  required maxlength="254"></label>
<label>Palavra-passe atual<input class="form-control" type="password" name="current_password" autocomplete="off" required></label>
<details><summary>Alterar palavra-passe</summary><label><input type="checkbox" name="change_password" value="1" data-enable-password-change> Definir uma nova palavra-passe</label><div class="vstack gap-3 mt-3"><label>Nova palavra-passe<input class="form-control" type="password" name="password" autocomplete="new-password" data-new-password disabled minlength="12"></label><label>Confirmar nova palavra-passe<input class="form-control" type="password" name="password_confirmation" autocomplete="new-password" data-new-password disabled></label></div></details>
<div><button class="btn btn-primary">Guardar alterações</button></div></form></section>
<section class="card p-4 mt-3"><h2 class="h5">Proteja a sua conta</h2><p>Configure a aplicação autenticadora e os códigos de recuperação.</p><a class="btn btn-outline-primary align-self-start" href="{{ route('security.edit','web') }}">Segurança da conta</a></section></div>
<script src="{{ route('cms-core.account-form-script',['v'=>'2.18.0']) }}" defer></script>
@endsection
