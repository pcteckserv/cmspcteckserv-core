@extends('cms-core::customers.layout')
@section('content')
<section style="max-width:480px;margin:24px auto"><h1>{{ ['login'=>'Iniciar sessão','register'=>'Criar conta','forgot'=>'Recuperar acesso','reset'=>'Nova palavra-passe'][$mode] }}</h1>
<form method="post" action="{{ route('customers.'.['login'=>'login.submit','register'=>'register.submit','forgot'=>'password.email','reset'=>'password.update'][$mode]) }}">@csrf
@if($mode==='register')<label>Nome<input name="name" value="{{ old('name') }}" autocomplete="name" required maxlength="190"></label>@endif
@if($mode==='reset')<input type="hidden" name="token" value="{{ $token }}">@endif
<label>Email<input type="email" name="email" value="{{ old('email', $email ?? '') }}" autocomplete="email" required></label>
@if($mode!=='forgot')<label>Palavra-passe<input type="password" name="password" autocomplete="{{ $mode==='login'?'current-password':'new-password' }}" required @if($mode!=='login') minlength="12" @endif></label>@endif
@if(in_array($mode,['register','reset']))<label>Confirmar palavra-passe<input type="password" name="password_confirmation" autocomplete="new-password" required></label>@endif
@if($mode==='register')<label><input type="checkbox" name="terms" value="1" required> Tomei conhecimento da <a href="{{ route('website.policy','politica-de-privacidade') }}">política de privacidade</a>@if(app(\Pcteckserv\CmsCore\Support\Website::class)->commerce()) e aceito os <a href="{{ route('website.policy','termos-e-condicoes') }}">termos e condições</a>@endif.</label>@endif
<button>Continuar</button></form>
<nav aria-label="Acesso à conta" style="display:flex;flex-wrap:wrap;gap:16px;margin-top:20px">
@if($mode!=='login')<a href="{{ \Pcteckserv\CmsCore\Customers\CustomerAuthReturn::link('customers.login') }}">Já tenho conta — Entrar</a>@endif
@if($mode!=='register' && app(\Pcteckserv\CmsCore\Support\Website::class)->registration())<a href="{{ \Pcteckserv\CmsCore\Customers\CustomerAuthReturn::link('customers.register') }}">Criar conta</a>@endif
@if($mode!=='forgot')<a href="{{ route('customers.password.forgot') }}">Recuperar palavra-passe</a>@endif
@if($returnTo=\Pcteckserv\CmsCore\Customers\CustomerAuthReturn::current(request()))<a href="{{ $returnTo }}">Continuar sem iniciar sessão</a>@endif
</nav></section>
@endsection
