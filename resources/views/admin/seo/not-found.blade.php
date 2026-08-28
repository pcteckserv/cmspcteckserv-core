@extends('cms-core::admin.layouts.app', ['title' => 'Erros 404'])

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h1 class="h3 mb-0">Erros 404</h1>
        <form method="GET" class="d-flex gap-2">
            <input class="form-control" name="search" value="{{ request('search') }}" placeholder="Pesquisar URL">
            <button class="btn btn-outline-primary">Pesquisar</button>
        </form>
    </div>
    @if (session('seo_success'))<div class="alert alert-success">{{ session('seo_success') }}</div>@endif
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>URL</th><th>Método</th><th>Ocorrências</th><th>Última ocorrência</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td class="text-break">{{ $item->url }}</td>
                            <td>{{ $item->method }}</td>
                            <td>{{ $item->hits }}</td>
                            <td>{{ $item->last_seen_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                @if ($item->is_resolved)<span class="badge text-bg-success">Resolvido</span>@elseif($item->is_ignored)<span class="badge text-bg-secondary">Ignorado</span>@else<span class="badge text-bg-warning">Pendente</span>@endif
                            </td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.seo.not-found.update', $item) }}" class="d-inline">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="is_resolved" value="1">
                                    <button class="btn btn-sm btn-outline-success">Resolver</button>
                                </form>
                                <form method="POST" action="{{ route('admin.seo.not-found.update', $item) }}" class="d-inline">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="is_ignored" value="1">
                                    <button class="btn btn-sm btn-outline-secondary">Ignorar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-secondary py-4">Não existem erros 404 registados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $items->links() }}</div>
    </div>
@endsection
