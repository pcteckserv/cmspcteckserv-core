@extends('cms-core::admin.layouts.app', ['title'=>'O meu perfil'])
@section('content')
<div style="max-width:760px"><h1 class="h3 mb-3">O meu perfil</h1>
@if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<p class="mb-1">{{ $error }}</p>@endforeach</div>@endif
<section class="card p-4"><form method="post" action="{{ route('admin.profile.update') }}" class="vstack gap-3">@csrf @method('PUT')
<label>Nome<input class="form-control" name="name" value="{{ old('name',$user->name) }}" autocomplete="name" required maxlength="190"></label>
<label>Email<input class="form-control" type="email" name="email" value="{{ old('email',$user->email) }}" autocomplete="email" required maxlength="254"></label>
<label>Palavra-passe atual<input class="form-control" type="password" name="current_password" autocomplete="current-password" required></label>
<details><summary>Alterar palavra-passe</summary><div class="vstack gap-3 mt-3"><label>Nova palavra-passe<input class="form-control" type="password" name="password" autocomplete="new-password" minlength="12"></label><label>Confirmar nova palavra-passe<input class="form-control" type="password" name="password_confirmation" autocomplete="new-password"></label></div></details>
<div><button class="btn btn-primary">Guardar alterações</button></div></form></section>
<section class="card p-4 mt-3"><h2 class="h5">Proteja a sua conta</h2><p>Configure a aplicação autenticadora e os códigos de recuperação.</p><a class="btn btn-outline-primary align-self-start" href="{{ route('security.edit','web') }}">Segurança da conta</a></section></div>
@endsection
