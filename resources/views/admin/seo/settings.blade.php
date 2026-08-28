@extends('cms-core::admin.layouts.app', ['title' => 'Configuração SEO'])

@section('content')
    <h1 class="h3 mb-4">Configuração SEO</h1>
    @if (session('seo_success'))<div class="alert alert-success">{{ session('seo_success') }}</div>@endif
    <form method="POST" action="{{ route('admin.seo.settings.update') }}" class="card border-0 shadow-sm">
        @csrf
        @method('PUT')
        <div class="card-body">
            <ul class="nav nav-tabs">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#seo-base" type="button">Geral</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#seo-org" type="button">Organização</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#seo-robots" type="button">Robots</button></li>
            </ul>
            <div class="tab-content pt-3">
                <div class="tab-pane fade show active" id="seo-base">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Nome do site</label><input class="form-control" name="seo_site_name" value="{{ old('seo_site_name', $options['seo_site_name']) }}" required></div>
                        <div class="col-md-6"><label class="form-label">URL base</label><input class="form-control" name="seo_base_url" value="{{ old('seo_base_url', $options['seo_base_url']) }}" required></div>
                        <div class="col-md-6"><label class="form-label">Título SEO padrão</label><input class="form-control" name="seo_default_title" value="{{ old('seo_default_title', $options['seo_default_title']) }}" required></div>
                        <div class="col-md-6"><label class="form-label">Template do título</label><input class="form-control" name="seo_title_template" value="{{ old('seo_title_template', $options['seo_title_template']) }}" required></div>
                        <div class="col-12"><label class="form-label">Meta description padrão</label><textarea class="form-control" name="seo_default_description" rows="3">{{ old('seo_default_description', $options['seo_default_description']) }}</textarea></div>
                        <div class="col-md-6"><label class="form-label">Imagem Open Graph padrão</label><input class="form-control" name="seo_default_og_image" value="{{ old('seo_default_og_image', $options['seo_default_og_image']) }}"></div>
                        <div class="col-md-6"><label class="form-label">Twitter Card padrão</label><select class="form-select" name="seo_twitter_card"><option @selected($options['seo_twitter_card'] === 'summary_large_image') value="summary_large_image">summary_large_image</option><option @selected($options['seo_twitter_card'] === 'summary') value="summary">summary</option></select></div>
                    </div>
                    <div class="row g-3 mt-2">
                        @foreach (['seo_default_robots_index' => 'Index por defeito', 'seo_default_robots_follow' => 'Follow por defeito', 'seo_auto_canonical' => 'Canonical automático', 'seo_generate_open_graph' => 'Gerar Open Graph', 'seo_generate_twitter_cards' => 'Gerar Twitter Cards', 'seo_generate_json_ld' => 'Gerar JSON-LD', 'seo_generate_sitemap' => 'Gerar sitemap', 'seo_generate_robots_txt' => 'Gerar robots.txt'] as $field => $label)
                            <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $options[$field]))><label class="form-check-label">{{ $label }}</label></div></div>
                        @endforeach
                    </div>
                </div>
                <div class="tab-pane fade" id="seo-org">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Nome da organização</label><input class="form-control" name="seo_organization_name" value="{{ old('seo_organization_name', $options['seo_organization_name']) }}"></div>
                        <div class="col-md-6"><label class="form-label">Tipo de organização</label><input class="form-control" name="seo_organization_type" value="{{ old('seo_organization_type', $options['seo_organization_type']) }}"></div>
                        <div class="col-md-6"><label class="form-label">Logo</label><input class="form-control" name="seo_organization_logo" value="{{ old('seo_organization_logo', $options['seo_organization_logo']) }}"></div>
                        <div class="col-md-3"><label class="form-label">Telefone</label><input class="form-control" name="seo_organization_phone" value="{{ old('seo_organization_phone', $options['seo_organization_phone']) }}"></div>
                        <div class="col-md-3"><label class="form-label">Email</label><input class="form-control" name="seo_organization_email" value="{{ old('seo_organization_email', $options['seo_organization_email']) }}"></div>
                        <div class="col-12"><label class="form-label">Morada</label><textarea class="form-control" name="seo_organization_address" rows="2">{{ old('seo_organization_address', $options['seo_organization_address']) }}</textarea></div>
                        <div class="col-12"><label class="form-label">Redes sociais</label><textarea class="form-control" name="seo_social_profiles" rows="3">{{ old('seo_social_profiles', $options['seo_social_profiles']) }}</textarea></div>
                    </div>
                </div>
                <div class="tab-pane fade" id="seo-robots">
                    <div class="alert alert-warning">Bloquear todo o site com <code>Disallow: /</code> não é permitido por defeito.</div>
                    <label class="form-label">Allow</label><textarea class="form-control" name="seo_robots_allow" rows="3">{{ old('seo_robots_allow', $options['seo_robots_allow']) }}</textarea>
                    <label class="form-label mt-3">Disallow</label><textarea class="form-control" name="seo_robots_disallow" rows="3">{{ old('seo_robots_disallow', $options['seo_robots_disallow']) }}</textarea>
                    <label class="form-label mt-3">Sitemap URL</label><input class="form-control" name="seo_robots_sitemap_url" value="{{ old('seo_robots_sitemap_url', $options['seo_robots_sitemap_url']) }}">
                    <label class="form-label mt-3">Modo avançado</label><textarea class="form-control font-monospace" name="seo_robots_advanced" rows="8">{{ old('seo_robots_advanced', $options['seo_robots_advanced']) }}</textarea>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white text-end"><button class="btn btn-primary">Guardar configuração</button></div>
    </form>
@endsection
