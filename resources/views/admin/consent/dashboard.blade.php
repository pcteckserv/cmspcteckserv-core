@extends('cms-core::admin.layouts.app', ['title' => 'Consentimentos'])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Consentimentos</h1>
        <p class="text-secondary mb-0">Gestão de cookies, tecnologias de tracking e configuração publicada.</p>
    </div>
    @can('consent.publish')
        <form method="POST" action="{{ route('admin.consent.settings.publish') }}" class="d-flex gap-2">
            @csrf
            <label class="form-check align-self-center"><input class="form-check-input" type="checkbox" name="increment_version" value="1"> pedir novo consentimento</label>
            <button class="btn btn-primary">Publicar</button>
        </form>
    @endcan
</div>

@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-secondary">Último scan</div><strong>{{ $lastScan?->finished_at?->format('d/m/Y H:i') ?? 'Sem análise' }}</strong></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-secondary">Serviços</div><strong>{{ $servicesCount }}</strong></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-secondary">Tecnologias</div><strong>{{ $technologiesCount }}</strong></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-secondary">Requerem revisão</div><strong>{{ $reviewCount }}</strong></div></div></div>
</div>

<div class="list-group">
    <a class="list-group-item list-group-item-action" href="{{ route('admin.consent.settings.edit') }}">Banner e textos</a>
    <a class="list-group-item list-group-item-action" href="{{ route('admin.consent.categories.index') }}">Categorias</a>
    <a class="list-group-item list-group-item-action" href="{{ route('admin.consent.services.index') }}">Serviços e tecnologias</a>
    <a class="list-group-item list-group-item-action" href="{{ route('admin.consent.scans.index') }}">Scanner e histórico</a>
</div>
@endsection
