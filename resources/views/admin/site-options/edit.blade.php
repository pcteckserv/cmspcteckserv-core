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
            <div class="col-lg-2">
                <h2 class="h5 mb-1">Footer</h2>
                <p class="text-secondary small mb-0">Rodapé público global.</p>
            </div>
            <div class="col-lg-8">
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="footer_enabled" value="0">
                    <input id="footer_enabled" name="footer_enabled" type="checkbox" class="form-check-input" value="1" @checked(old('footer_enabled', $options['footer_enabled']))>
                    <label class="form-check-label" for="footer_enabled">Mostrar footer</label>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="footer_copyright_text">Texto de copyright</label>
                    <input id="footer_copyright_text" name="footer_copyright_text" type="text" class="form-control @error('footer_copyright_text') is-invalid @enderror" value="{{ old('footer_copyright_text', $options['footer_copyright_text']) }}" required>
                    <div class="form-text">O ano e o título do site são adicionados automaticamente.</div>
                    @error('footer_copyright_text')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="footer_show_pcteckserv_credit" value="0">
                    <input id="footer_show_pcteckserv_credit" name="footer_show_pcteckserv_credit" type="checkbox" class="form-check-input" value="1" @checked(old('footer_show_pcteckserv_credit', $options['footer_show_pcteckserv_credit']))>
                    <label class="form-check-label" for="footer_show_pcteckserv_credit">Mostrar crédito PCTECKSERV</label>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="footer_credit_text">Texto do crédito</label>
                        <input id="footer_credit_text" name="footer_credit_text" type="text" class="form-control @error('footer_credit_text') is-invalid @enderror" value="{{ old('footer_credit_text', $options['footer_credit_text']) }}" required>
                        @error('footer_credit_text')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="footer_pcteckserv_url">URL PCTECKSERV</label>
                        <input id="footer_pcteckserv_url" name="footer_pcteckserv_url" type="url" class="form-control @error('footer_pcteckserv_url') is-invalid @enderror" value="{{ old('footer_pcteckserv_url', $options['footer_pcteckserv_url']) }}" required>
                        @error('footer_pcteckserv_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-3 mt-0">
                    <div class="col-md-6">
                        <label class="form-label" for="footer_pcteckserv_logo_media_id">ID de media do logótipo</label>
                        <input id="footer_pcteckserv_logo_media_id" name="footer_pcteckserv_logo_media_id" type="number" min="1" class="form-control @error('footer_pcteckserv_logo_media_id') is-invalid @enderror" value="{{ old('footer_pcteckserv_logo_media_id', $options['footer_pcteckserv_logo_media_id']) }}">
                        <div class="form-text">Opcional. Se preenchido, usa o Media Manager.</div>
                        @error('footer_pcteckserv_logo_media_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="footer_pcteckserv_logo_path">Path fallback do logótipo</label>
                        <input id="footer_pcteckserv_logo_path" name="footer_pcteckserv_logo_path" type="text" class="form-control @error('footer_pcteckserv_logo_path') is-invalid @enderror" value="{{ old('footer_pcteckserv_logo_path', $options['footer_pcteckserv_logo_path']) }}">
                        <div class="form-text">Path relativo no disco público. Não usar caminhos absolutos.</div>
                        @error('footer_pcteckserv_logo_path')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 align-items-start mb-4">
            <div class="col-lg-2">
                <h2 class="h5 mb-1">Cores</h2>
            </div>
            <div class="col-lg-8">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="footer_background_color">Fundo</label>
                        <input id="footer_background_color" name="footer_background_color" type="color" class="form-control form-control-color @error('footer_background_color') is-invalid @enderror" value="{{ old('footer_background_color', $options['footer_background_color']) }}" required>
                        @error('footer_background_color')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="footer_text_color">Texto</label>
                        <input id="footer_text_color" name="footer_text_color" type="color" class="form-control form-control-color @error('footer_text_color') is-invalid @enderror" value="{{ old('footer_text_color', $options['footer_text_color']) }}" required>
                        @error('footer_text_color')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="footer_secondary_text_color">Texto secundário</label>
                        <input id="footer_secondary_text_color" name="footer_secondary_text_color" type="color" class="form-control form-control-color @error('footer_secondary_text_color') is-invalid @enderror" value="{{ old('footer_secondary_text_color', $options['footer_secondary_text_color']) }}" required>
                        @error('footer_secondary_text_color')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 align-items-start mb-4">
            <div class="col-lg-2">
                <h2 class="h5 mb-1">Espaçamento</h2>
            </div>
            <div class="col-lg-8">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="footer_padding_y">Padding vertical</label>
                        <input id="footer_padding_y" name="footer_padding_y" type="number" min="8" max="96" class="form-control @error('footer_padding_y') is-invalid @enderror" value="{{ old('footer_padding_y', $options['footer_padding_y']) }}" required>
                        @error('footer_padding_y')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="footer_padding_x">Padding horizontal</label>
                        <input id="footer_padding_x" name="footer_padding_x" type="number" min="8" max="96" class="form-control @error('footer_padding_x') is-invalid @enderror" value="{{ old('footer_padding_x', $options['footer_padding_x']) }}" required>
                        @error('footer_padding_x')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="footer_max_width">Largura máxima</label>
                        <input id="footer_max_width" name="footer_max_width" type="number" min="320" max="1920" class="form-control @error('footer_max_width') is-invalid @enderror" value="{{ old('footer_max_width', $options['footer_max_width']) }}" required>
                        @error('footer_max_width')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 align-items-start mb-4">
            <div class="col-lg-2">
                <h2 class="h5 mb-1">Preview</h2>
            </div>
            <div class="col-lg-8">
                <div class="cms-footer-preview">
                    <div class="cms-footer-preview__copyright">
                        &copy; {{ now()->year }}. {{ old('site_title', $options['site_title']) }} - {{ old('footer_copyright_text', $options['footer_copyright_text']) }}
                    </div>
                    <div class="cms-footer-preview__credit">
                        {{ old('footer_credit_text', $options['footer_credit_text']) }} <strong>PCTECKSERV</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="offset-lg-2 col-lg-6">
                <button class="btn btn-primary" type="submit">Guardar alterações</button>
            </div>
        </div>
    </form>
@endsection
