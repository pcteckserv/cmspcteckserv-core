<section class="bg-white border rounded p-4 mt-4" aria-labelledby="two-factor-title">
    <h2 class="h5" id="two-factor-title">Autenticação de dois fatores</h2>
    <p class="text-secondary">Proteja o acesso à administração com um código do Google Authenticator ou de outra aplicação de autenticação compatível.</p>
    @error('two_factor')<div class="alert alert-danger" role="alert">{{ $message }}</div>@enderror
    @error('two_factor_code')<div class="alert alert-danger" role="alert">{{ $message }}</div>@enderror

    @if ($twoFactorEnabled)
        <p><span class="badge text-bg-success">Ativa</span></p>
        <p>Será pedido um código da aplicação ao iniciar sessão. Guarde os códigos de recuperação num local seguro.</p>
        <form method="POST" action="{{ route('admin.profile.two-factor.regenerate') }}">
            @csrf
            @include('cms-core::admin.profile.partials.two-factor-verification')
            <div class="d-flex flex-wrap gap-2 mt-3">
                <button class="btn btn-outline-primary" type="submit">Gerar novos códigos de recuperação</button>
                <button class="btn btn-outline-danger" type="submit" name="_method" value="DELETE" formaction="{{ route('admin.profile.two-factor.disable') }}">Desativar autenticação de dois fatores</button>
            </div>
        </form>
    @elseif ($twoFactorSetup)
        <p>1. Leia este código QR com a aplicação de autenticação ou introduza a chave manualmente.</p>
        <img class="img-fluid mb-3" src="{{ $twoFactorSetup['qr'] }}" alt="Código QR para configurar a autenticação de dois fatores" width="240" height="240">
        <p>Chave de configuração: <code class="text-break">{{ $twoFactorSetup['secret'] }}</code></p>
        <p>2. Indique a palavra-passe atual e o código de seis algarismos apresentado na aplicação. A configuração expira ao fim de 10 minutos.</p>
        <form method="POST" action="{{ route('admin.profile.two-factor.confirm') }}">
            @csrf
            @include('cms-core::admin.profile.partials.two-factor-verification')
            <button class="btn btn-primary mt-3" type="submit">Confirmar e ativar</button>
        </form>
    @else
        <p><span class="badge text-bg-secondary">Inativa</span></p>
        <form method="POST" action="{{ route('admin.profile.two-factor.begin') }}">
            @csrf
            <label class="form-label" for="two-factor-password">Palavra-passe atual</label>
            <input class="form-control @error('current_password') is-invalid @enderror" id="two-factor-password" name="current_password" type="password" autocomplete="current-password" required>
            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <button class="btn btn-primary mt-3" type="submit">Configurar autenticação de dois fatores</button>
        </form>
    @endif
</section>
