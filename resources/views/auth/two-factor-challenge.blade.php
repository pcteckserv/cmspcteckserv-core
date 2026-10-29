@extends('cms-core::admin.layouts.guest', ['title' => 'Verificar autenticação'])

@section('content')
    <main class="min-vh-100 d-flex align-items-center py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-7 col-lg-5">
                    <div class="bg-white border rounded-2 shadow-sm p-4">
                        <h1 class="h3">Autenticação de dois fatores</h1>
                        <p>Indique o código da aplicação de autenticação ou um dos seus códigos de recuperação.</p>
                        <form method="POST" action="{{ route('two-factor.verify') }}">
                            @csrf
                            <label class="form-label" for="two-factor-code">Código de autenticação</label>
                            <input class="form-control @error('two_factor_code') is-invalid @enderror" id="two-factor-code" name="two_factor_code" type="text" maxlength="64" autocomplete="one-time-code" required autofocus>
                            @error('two_factor_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <button class="btn btn-primary w-100 mt-3" type="submit">Verificar e entrar</button>
                        </form>
                        <form method="POST" action="{{ route('two-factor.cancel') }}" class="mt-3">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-outline-secondary w-100" type="submit">Voltar ao início de sessão</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
