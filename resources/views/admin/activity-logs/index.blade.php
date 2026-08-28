@extends('cms-core::admin.layouts.app', ['title' => 'Logs de Atividade'])

@section('content')
    <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Logs de Atividade</h1>
            <p class="text-secondary mb-0">Consulta centralizada de auditoria administrativa.</p>
        </div>

        @can('core.activity-logs.export')
            <a class="btn btn-outline-secondary" href="{{ route('admin.activity-logs.export', request()->query()) }}">Exportar CSV</a>
        @endcan
    </div>

    <form class="card border-0 shadow-sm mb-4" method="GET" action="{{ route('admin.activity-logs.index') }}">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label" for="search">Pesquisa</label>
                    <input class="form-control" id="search" name="search" value="{{ $filters['search'] ?? '' }}" type="search">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="user_id">Utilizador</label>
                    <select class="form-select" id="user_id" name="user_id">
                        <option value="">Todos</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected(($filters['user_id'] ?? '') == $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="category">Categoria</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">Todas</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="action">Ação</label>
                    <select class="form-select" id="action" name="action">
                        <option value="">Todas</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="ip">IP</label>
                    <input class="form-control" id="ip" name="ip" value="{{ $filters['ip'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="date_from">Desde</label>
                    <input class="form-control" id="date_from" name="date_from" value="{{ $filters['date_from'] ?? '' }}" type="date">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="date_to">Até</label>
                    <input class="form-control" id="date_to" name="date_to" value="{{ $filters['date_to'] ?? '' }}" type="date">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="subject_type">Tipo de entidade</label>
                    <input class="form-control" id="subject_type" name="subject_type" value="{{ $filters['subject_type'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="subject_id">ID da entidade</label>
                    <input class="form-control" id="subject_id" name="subject_id" value="{{ $filters['subject_id'] ?? '' }}" type="number" min="1">
                </div>
                <div class="col-md-1">
                    <label class="form-label" for="per_page">Por página</label>
                    <select class="form-select" id="per_page" name="per_page">
                        @foreach ([25, 50, 100] as $option)
                            <option value="{{ $option }}" @selected((int) ($filters['per_page'] ?? 25) === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button class="btn btn-primary" type="submit">Filtrar</button>
                <a class="btn btn-outline-secondary" href="{{ route('admin.activity-logs.index') }}">Limpar</a>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Utilizador</th>
                        <th>Ação</th>
                        <th>Categoria</th>
                        <th>Descrição</th>
                        <th>IP</th>
                        <th class="text-end">Detalhes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $log->created_at?->timezone(config('cms-core.admin_timezone', 'Europe/Lisbon'))->format('d/m/Y H:i') }}</td>
                            <td>{{ $log->user?->name ?? 'Sistema' }}</td>
                            <td><code>{{ $log->action }}</code></td>
                            <td>{{ $log->category }}</td>
                            <td>{{ $log->description }}</td>
                            <td>{{ $log->ip_address }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.activity-logs.show', $log) }}">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-center text-secondary py-4" colspan="7">Não existem logs de atividade para os filtros selecionados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">
            {{ $logs->links() }}
        </div>
    </div>
@endsection
