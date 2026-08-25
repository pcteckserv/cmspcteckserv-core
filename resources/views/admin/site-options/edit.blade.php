@extends('cms-core::admin.layouts.app', ['title' => 'Opções gerais'])

@section('content')
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Opções gerais</h1>
            <p class="text-secondary mb-0">Parâmetros globais usados como fallback nos sites dos clientes.</p>
        </div>
    </div>

    @if (session('cms_site_options_success'))
        <div class="alert alert-success">{{ session('cms_site_options_success') }}</div>
    @endif

    <form class="bg-white border rounded-2 p-4 cms-settings-form" method="POST" action="{{ route('admin.site-options.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-3 align-items-start mb-4">
            <label class="col-lg-2 col-form-label fw-semibold" for="site_title">Título do site</label>
            <div class="col-lg-5">
                <input id="site_title" name="site_title" type="text" class="form-control @error('site_title') is-invalid @enderror" value="{{ old('site_title', $options['site_title']) }}" required>
                @error('site_title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row g-3 align-items-start mb-4">
            <label class="col-lg-2 col-form-label fw-semibold" for="site_description">Descrição</label>
            <div class="col-lg-6">
                <input id="site_description" name="site_description" type="text" class="form-control @error('site_description') is-invalid @enderror" value="{{ old('site_description', $options['site_description']) }}">
                <div class="form-text">Em poucas palavras, explique do que trata este site.</div>
                @error('site_description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row g-3 align-items-start mb-4">
            <label class="col-lg-2 col-form-label fw-semibold" for="site_icon_url">Ícone do site</label>
            <div class="col-lg-6">
                <input id="site_icon_file" name="site_icon_file" type="file" class="form-control mb-2 @error('site_icon_file') is-invalid @enderror" accept=".png,.jpg,.jpeg,.webp,.ico">
                @error('site_icon_file')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="input-group">
                    <input id="site_icon_url" name="site_icon_url" type="text" class="form-control @error('site_icon_url') is-invalid @enderror" value="{{ old('site_icon_url', $options['site_icon_url']) }}" placeholder="/favicon.ico">
                    <button class="btn btn-outline-danger" type="submit" name="remove_site_icon" value="1">Remover</button>
                </div>
                <div class="form-text">Escolha um ficheiro ou indique uma URL. O ícone deve ser quadrado e ter pelo menos 512 por 512 píxeis.</div>
                @error('site_icon_url')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row g-3 align-items-start mb-4">
            <label class="col-lg-2 col-form-label fw-semibold" for="site_url">Endereço do site (URL)</label>
            <div class="col-lg-5">
                <input id="site_url" name="site_url" type="url" class="form-control @error('site_url') is-invalid @enderror" value="{{ old('site_url', $options['site_url']) }}" required>
                <div class="form-text">Digite aqui o endereço público principal do site do cliente.</div>
                @error('site_url')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row g-3 align-items-start mb-4">
            <label class="col-lg-2 col-form-label fw-semibold" for="admin_email">Endereço de email de administração</label>
            <div class="col-lg-5">
                <input id="admin_email" name="admin_email" type="email" class="form-control @error('admin_email') is-invalid @enderror" value="{{ old('admin_email', $options['admin_email']) }}" required>
                <div class="form-text">Este endereço é utilizado para fins administrativos.</div>
                @error('admin_email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row g-3 align-items-start mb-4">
            <label class="col-lg-2 col-form-label fw-semibold" for="locale">Idioma do site</label>
            <div class="col-lg-4">
                <select id="locale" name="locale" class="form-select @error('locale') is-invalid @enderror" required>
                    @foreach ($locales as $value => $label)
                        <option value="{{ $value }}" @selected(old('locale', $options['locale']) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('locale')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <hr class="my-4">

        <div class="row g-3 align-items-start mb-4">
            <div class="col-lg-2 fw-semibold">SMTP</div>
            <div class="col-lg-6">
                <div class="form-check form-switch">
                    <input id="smtp_enabled" name="smtp_enabled" type="checkbox" class="form-check-input @error('smtp_enabled') is-invalid @enderror" value="1" @checked(old('smtp_enabled', $options['smtp_enabled']) == '1')>
                    <label class="form-check-label" for="smtp_enabled">Ativar envio por SMTP</label>
                    @error('smtp_enabled')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-text">Quando ativo, estes dados passam a ser a configuracao global de email do site.</div>
            </div>
        </div>

        <div class="row g-3 align-items-start mb-4">
            <label class="col-lg-2 col-form-label fw-semibold" for="smtp_host">Servidor SMTP</label>
            <div class="col-lg-5">
                <input id="smtp_host" name="smtp_host" type="text" class="form-control @error('smtp_host') is-invalid @enderror" value="{{ old('smtp_host', $options['smtp_host']) }}" placeholder="smtp.exemplo.pt">
                @error('smtp_host')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row g-3 align-items-start mb-4">
            <label class="col-lg-2 col-form-label fw-semibold" for="smtp_port">Porta</label>
            <div class="col-lg-2">
                <input id="smtp_port" name="smtp_port" type="number" min="1" max="65535" class="form-control @error('smtp_port') is-invalid @enderror" value="{{ old('smtp_port', $options['smtp_port']) }}">
                @error('smtp_port')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <label class="col-lg-1 col-form-label fw-semibold" for="smtp_encryption">Seguranca</label>
            <div class="col-lg-2">
                <select id="smtp_encryption" name="smtp_encryption" class="form-select @error('smtp_encryption') is-invalid @enderror">
                    @foreach (['' => 'Nenhuma', 'tls' => 'TLS', 'ssl' => 'SSL'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('smtp_encryption', $options['smtp_encryption']) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('smtp_encryption')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row g-3 align-items-start mb-4">
            <label class="col-lg-2 col-form-label fw-semibold" for="smtp_username">Utilizador SMTP</label>
            <div class="col-lg-5">
                <input id="smtp_username" name="smtp_username" type="text" class="form-control @error('smtp_username') is-invalid @enderror" value="{{ old('smtp_username', $options['smtp_username']) }}" autocomplete="username">
                @error('smtp_username')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row g-3 align-items-start mb-4">
            <label class="col-lg-2 col-form-label fw-semibold" for="smtp_password">Password SMTP</label>
            <div class="col-lg-5">
                <input id="smtp_password" name="smtp_password" type="password" class="form-control @error('smtp_password') is-invalid @enderror" value="" autocomplete="new-password" placeholder="{{ empty($options['smtp_password']) ? '' : 'Password configurada' }}">
                <div class="form-text">Deixe em branco para manter a password atual.</div>
                @error('smtp_password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row g-3 align-items-start mb-4">
            <label class="col-lg-2 col-form-label fw-semibold" for="smtp_from_address">Email do remetente</label>
            <div class="col-lg-5">
                <input id="smtp_from_address" name="smtp_from_address" type="email" class="form-control @error('smtp_from_address') is-invalid @enderror" value="{{ old('smtp_from_address', $options['smtp_from_address']) }}">
                @error('smtp_from_address')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row g-3 align-items-start mb-4">
            <label class="col-lg-2 col-form-label fw-semibold" for="smtp_from_name">Nome do remetente</label>
            <div class="col-lg-5">
                <input id="smtp_from_name" name="smtp_from_name" type="text" class="form-control @error('smtp_from_name') is-invalid @enderror" value="{{ old('smtp_from_name', $options['smtp_from_name']) }}">
                @error('smtp_from_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row">
            <div class="offset-lg-2 col-lg-6">
                <button class="btn btn-primary" type="submit">Guardar alterações</button>
            </div>
        </div>
    </form>
@endsection
