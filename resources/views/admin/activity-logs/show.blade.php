@extends('cms-core::admin.layouts.app', ['title' => 'Detalhe do Log'])

@section('content')
    <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Detalhe do Log</h1>
            <p class="text-secondary mb-0"><code>{{ $log->action }}</code></p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.activity-logs.index') }}">Voltar</a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Data/hora</dt>
                <dd class="col-sm-9">{{ $log->created_at?->timezone(config('cms-core.admin_timezone', 'Europe/Lisbon'))->format('d/m/Y H:i:s') }}</dd>
                <dt class="col-sm-3">Utilizador</dt>
                <dd class="col-sm-9">{{ $log->user?->name ?? 'Sistema' }}</dd>
                <dt class="col-sm-3">Ação</dt>
                <dd class="col-sm-9"><code>{{ $log->action }}</code></dd>
                <dt class="col-sm-3">Categoria</dt>
                <dd class="col-sm-9">{{ $log->category }}</dd>
                <dt class="col-sm-3">Descrição</dt>
                <dd class="col-sm-9">{{ $log->description }}</dd>
                <dt class="col-sm-3">IP</dt>
                <dd class="col-sm-9">{{ $log->ip_address ?? 'N/D' }}</dd>
                <dt class="col-sm-3">User Agent</dt>
                <dd class="col-sm-9">{{ $log->user_agent ?? 'N/D' }}</dd>
                <dt class="col-sm-3">URL</dt>
                <dd class="col-sm-9">{{ $log->url ?? 'N/D' }}</dd>
                <dt class="col-sm-3">Método HTTP</dt>
                <dd class="col-sm-9">{{ $log->http_method ?? 'N/D' }}</dd>
                <dt class="col-sm-3">Entidade</dt>
                <dd class="col-sm-9">{{ $log->subject_type ? $log->subject_type.' #'.$log->subject_id : 'N/D' }}</dd>
            </dl>
        </div>
    </div>

    <div class="row g-4">
        @foreach (['properties' => 'Properties', 'old_values' => 'Valores anteriores', 'new_values' => 'Valores novos'] as $field => $label)
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">{{ $label }}</div>
                    <div class="card-body">
                        <pre class="mb-0 small text-break">{{ json_encode($log->{$field} ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
