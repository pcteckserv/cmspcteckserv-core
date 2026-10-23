@extends($guard === 'web' ? 'cms-core::admin.layouts.app' : 'cms-core::customers.layout', ['title'=>'Segurança da conta'])
@section('content')
<section class="card p-4" style="max-width:820px">
    <h1 class="h3">Segurança da conta</h1><p class="text-secondary">{{ $user->email }}</p>
    @if($guard==='web' && session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
    @if($guard==='web' && $errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @if(session('recovery_codes'))
        <div class="alert alert-info"><h2 class="h5">Códigos de recuperação</h2><p>Guarde estes códigos num local seguro. Cada código só pode ser usado uma vez e só é mostrado agora.</p>@foreach(session('recovery_codes') as $code)<div><code>{{ $code }}</code></div>@endforeach</div>
    @endif
    @if($factor?->confirmed_at)
        <p>Autenticação em dois passos ativa. Códigos de recuperação disponíveis: {{ count($factor->recovery_codes ?? []) }}.</p>
        <form method="post" action="{{ route('security.disable',$guard) }}" class="vstack gap-3">@csrf @method('DELETE')
            <label>Palavra-passe atual<input class="form-control" type="password" name="password" required autocomplete="current-password"></label>
            <label>Código da aplicação ou de recuperação<input class="form-control" name="code" autocomplete="one-time-code" required maxlength="30"></label>
            <div><button class="btn btn-outline-danger">Desativar 2FA</button></div>
        </form>
    @elseif($factor)
        <h2 class="h5">Adicionar à aplicação autenticadora</h2>
        <div style="display:flex;flex-wrap:wrap;gap:24px;align-items:center;margin:16px 0">
            <img src="{{ $qr }}" width="280" height="280" alt="QR code para configurar a aplicação autenticadora" style="max-width:100%;height:auto;background:white;border-radius:12px" data-authenticator-qr>
            <div style="flex:1 1 260px;min-width:0" data-qr-instructions><p>Na aplicação autenticadora, escolha adicionar conta e leia este QR code.</p><p>Depois introduza o código de seis dígitos para concluir a ativação.</p>
                <details><summary>Não consegue ler o QR code?</summary><p>Adicione uma conta baseada no tempo (TOTP) com esta chave:</p><code style="overflow-wrap:anywhere;word-break:break-all">{{ $factor->secret }}</code><p>{{ config('app.name') }} / {{ $user->email }}</p></details>
            </div>
        </div>
        <form method="post" action="{{ route('security.confirm',$guard) }}" class="vstack gap-3">@csrf
            <label>Palavra-passe atual<input class="form-control" type="password" name="password" required autocomplete="current-password"></label>
            <label>Código de seis dígitos<input class="form-control" name="code" pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required></label>
            <div><button class="btn btn-primary">Confirmar e ativar 2FA</button></div>
        </form>
    @else
        <p>Proteja a sua conta com uma aplicação autenticadora e códigos de recuperação.</p>
        <form method="post" action="{{ route('security.setup',$guard) }}" class="vstack gap-3">@csrf
            <label>Palavra-passe atual<input class="form-control" type="password" name="password" autocomplete="current-password" required></label>
            <div><button class="btn btn-primary">Configurar 2FA</button></div>
        </form>
    @endif
</section>
@endsection
