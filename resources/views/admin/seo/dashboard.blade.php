@extends('cms-core::admin.layouts.app', ['title' => 'SEO'])

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">SEO</h1>
            <p class="text-secondary mb-0">Visão geral técnica da optimização orgânica do site.</p>
        </div>
        <a class="btn btn-outline-primary" href="{{ route('admin.seo.settings.edit') }}">Configuração geral</a>
    </div>

    <div class="row g-3">
        @foreach ([
            'SEO Score médio' => $averageScore,
            'Páginas analisadas' => $auditedPages,
            'Problemas críticos' => $criticalIssues,
            'Avisos' => $warnings,
            'Erros 404' => $notFoundCount,
            'Redirecionamentos' => $redirectCount,
        ] as $label => $value)
            <div class="col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-secondary small">{{ $label }}</div>
                        <div class="display-6">{{ $value }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body">
            <h2 class="h5">Problemas prioritários</h2>
            <p class="text-secondary mb-0">Execute auditorias para preencher recomendações técnicas por página.</p>
        </div>
    </div>
@endsection
