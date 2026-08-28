@php($seoMeta = $seoMeta ?? (($model ?? null) && method_exists($model, 'seo') ? $model->seo : null))
@php($value = fn (string $field, mixed $fallback = null) => old($prefix.'.'.$field, data_get($seoMeta, $field, $fallback)))
<section class="card border-0 shadow-sm" data-cms-seo-editor>
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
            <div>
                <h2 class="h5 mb-1">SEO da página</h2>
                <p class="text-secondary mb-0">Configure a apresentação técnica desta página nos motores de pesquisa e redes sociais.</p>
            </div>
            <span class="badge text-bg-info align-self-start" data-seo-score>SEO Score 0/100</span>
        </div>

        <div class="border rounded p-3 mb-3 bg-light">
            <div class="small text-success" data-seo-preview-url>{{ url()->current() }}</div>
            <div class="fs-5 text-primary" data-seo-preview-title>{{ $value('title', 'Título da página') }}</div>
            <div class="small text-secondary" data-seo-preview-description>{{ $value('description', 'Descrição da página.') }}</div>
        </div>

        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#seo-geral" type="button">Geral</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#seo-social" type="button">Social</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#seo-schema" type="button">Schema</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#seo-avancado" type="button">Avançado</button></li>
        </ul>

        <div class="tab-content pt-3">
            <div class="tab-pane fade show active" id="seo-geral">
                <label class="form-label" for="seo-title">Título SEO</label>
                <input id="seo-title" class="form-control" name="{{ $prefix }}[title]" value="{{ $value('title') }}" maxlength="120" data-seo-title>
                <div class="form-text"><span data-seo-title-count>0</span>/60 recomendado.</div>

                <label class="form-label mt-3" for="seo-description">Meta description</label>
                <textarea id="seo-description" class="form-control" name="{{ $prefix }}[description]" rows="3" maxlength="320" data-seo-description>{{ $value('description') }}</textarea>
                <div class="form-text"><span data-seo-description-count>0</span>/160 recomendado.</div>

                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="{{ $prefix }}[robots_index]" value="1" @checked($value('robots_index', true))>
                            <label class="form-check-label">Permitir indexação</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="{{ $prefix }}[robots_follow]" value="1" @checked($value('robots_follow', true))>
                            <label class="form-check-label">Seguir links</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="seo-social">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Open Graph title</label><input class="form-control" name="{{ $prefix }}[og_title]" value="{{ $value('og_title') }}"></div>
                    <div class="col-md-6"><label class="form-label">Open Graph image</label><input class="form-control" name="{{ $prefix }}[og_image]" value="{{ $value('og_image') }}" placeholder="https://"></div>
                    <div class="col-12"><label class="form-label">Open Graph description</label><textarea class="form-control" name="{{ $prefix }}[og_description]" rows="2">{{ $value('og_description') }}</textarea></div>
                    <div class="col-md-6"><label class="form-label">Twitter title</label><input class="form-control" name="{{ $prefix }}[twitter_title]" value="{{ $value('twitter_title') }}"></div>
                    <div class="col-md-6"><label class="form-label">Twitter image</label><input class="form-control" name="{{ $prefix }}[twitter_image]" value="{{ $value('twitter_image') }}" placeholder="https://"></div>
                    <div class="col-12"><label class="form-label">Twitter description</label><textarea class="form-control" name="{{ $prefix }}[twitter_description]" rows="2">{{ $value('twitter_description') }}</textarea></div>
                </div>
            </div>

            <div class="tab-pane fade" id="seo-schema">
                <label class="form-label">Tipo de schema</label>
                <input class="form-control" name="{{ $prefix }}[schema_type]" value="{{ $value('schema_type') }}" placeholder="WebPage">
                <label class="form-label mt-3">JSON-LD adicional</label>
                <textarea class="form-control font-monospace" name="{{ $prefix }}[schema_data]" rows="5">{{ json_encode($value('schema_data', []), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</textarea>
            </div>

            <div class="tab-pane fade" id="seo-avancado">
                <label class="form-label">Canonical customizado</label>
                <input class="form-control" name="{{ $prefix }}[canonical_url]" value="{{ $value('canonical_url') }}" placeholder="https://">
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" name="{{ $prefix }}[exclude_from_sitemap]" value="1" @checked($value('exclude_from_sitemap', false))>
                    <label class="form-check-label">Excluir do sitemap</label>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.querySelectorAll('[data-cms-seo-editor]').forEach((editor) => {
    const title = editor.querySelector('[data-seo-title]');
    const description = editor.querySelector('[data-seo-description]');
    const titleCount = editor.querySelector('[data-seo-title-count]');
    const descriptionCount = editor.querySelector('[data-seo-description-count]');
    const previewTitle = editor.querySelector('[data-seo-preview-title]');
    const previewDescription = editor.querySelector('[data-seo-preview-description]');
    const score = editor.querySelector('[data-seo-score]');
    const update = () => {
        titleCount.textContent = title.value.length;
        descriptionCount.textContent = description.value.length;
        previewTitle.textContent = title.value || 'Título da página';
        previewDescription.textContent = description.value || 'Descrição da página.';
        let points = 100;
        if (!title.value) points -= 35;
        if (!description.value) points -= 25;
        if (title.value.length > 60) points -= 10;
        if (description.value.length > 160) points -= 10;
        score.textContent = `SEO Score ${Math.max(0, points)}/100`;
    };
    title.addEventListener('input', update);
    description.addEventListener('input', update);
    update();
});
</script>
