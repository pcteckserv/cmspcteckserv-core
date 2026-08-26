@extends('cms-core::admin.layouts.app', ['title' => 'Footer'])

@section('content')
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Footer</h1>
            <p class="text-secondary mb-0">Configuração do rodapé público global.</p>
        </div>
    </div>

    @if (session('cms_footer_settings_success'))
        <div class="alert alert-success">{{ session('cms_footer_settings_success') }}</div>
    @endif

    <form class="bg-white border rounded-2 p-4 cms-settings-form" method="POST" action="{{ route('admin.footer.update') }}">
        @csrf
        @method('PUT')
        <input id="site_title" type="hidden" value="{{ $options['site_title'] }}">

        <div class="row g-3 align-items-start mb-4">
            <div class="col-lg-2">
                <h2 class="h5 mb-1">Estado</h2>
            </div>
            <div class="col-lg-8">
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="footer_enabled" value="0">
                    <input id="footer_enabled" name="footer_enabled" type="checkbox" class="form-check-input" value="1" @checked(old('footer_enabled', $options['footer_enabled']))>
                    <label class="form-check-label" for="footer_enabled">Mostrar footer</label>
                </div>
                <div class="form-text">O nome do site é obtido automaticamente das Opções gerais.</div>
            </div>
        </div>

        <div class="row g-3 align-items-start mb-4">
            <label class="col-lg-2 col-form-label fw-semibold" for="footer_copyright_text">Copyright</label>
            <div class="col-lg-8">
                <input id="footer_copyright_text" name="footer_copyright_text" type="text" class="form-control @error('footer_copyright_text') is-invalid @enderror" value="{{ old('footer_copyright_text', $options['footer_copyright_text']) }}" required>
                <div class="form-text">O ano e o título do site são adicionados automaticamente.</div>
                @error('footer_copyright_text')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row g-3 align-items-start mb-4">
            <div class="col-lg-2">
                <h2 class="h5 mb-1">Crédito</h2>
            </div>
            <div class="col-lg-8">
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
                        &copy; {{ now()->year }}. {{ $options['site_title'] }} - {{ old('footer_copyright_text', $options['footer_copyright_text']) }}
                    </div>
                    <div class="cms-footer-preview__credit">
                        {{ old('footer_credit_text', $options['footer_credit_text']) }} <strong>PCTECKSERV</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="offset-lg-2 col-lg-8">
                <button class="btn btn-primary" type="submit">Guardar footer</button>
            </div>
        </div>
    </form>
@endsection
