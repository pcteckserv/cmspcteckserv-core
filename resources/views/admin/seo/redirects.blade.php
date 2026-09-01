@extends('cms-core::admin.layouts.app', ['title' => 'Redirecionamentos SEO'])

@section('content')
    <h1 class="h3 mb-4">Redirecionamentos</h1>
    @if (session('seo_success'))<div class="alert alert-success">{{ session('seo_success') }}</div>@endif

    <form method="POST" action="{{ route('admin.seo.redirects.store') }}" class="card border-0 shadow-sm mb-4">
        @csrf
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4"><label class="form-label">Origem</label><input class="form-control" name="source" placeholder="/url-antiga" required></div>
                <div class="col-md-4"><label class="form-label">Destino</label><input class="form-control" name="destination" placeholder="/url-nova" required></div>
                <div class="col-md-2"><label class="form-label">Código</label><select class="form-select" name="status_code"><option>301</option><option>302</option><option>307</option><option>308</option></select></div>
                <div class="col-md-1"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked><label class="form-check-label">Ativo</label></div></div>
                <div class="col-md-1"><button class="btn btn-primary w-100">Criar</button></div>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Origem</th><th>Destino</th><th>Código</th><th>Hits</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                    @forelse ($redirects as $redirect)
                        <tr>
                            <form method="POST" action="{{ route('admin.seo.redirects.update', $redirect) }}">
                                @csrf @method('PUT')
                                <td><input class="form-control form-control-sm" name="source" value="{{ $redirect->source }}"></td>
                                <td><input class="form-control form-control-sm" name="destination" value="{{ $redirect->destination }}"></td>
                                <td><select class="form-select form-select-sm" name="status_code">@foreach ([301,302,307,308] as $code)<option @selected($redirect->status_code === $code)>{{ $code }}</option>@endforeach</select></td>
                                <td>{{ $redirect->hits }}</td>
                                <td><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($redirect->is_active) aria-label="Ativo"></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary">Guardar</button>
                            </form>
                                    <form method="POST" action="{{ route('admin.seo.redirects.destroy', $redirect) }}" class="d-inline" onsubmit="return confirm('Eliminar este redirecionamento?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger cms-media-icon-button" type="submit" title="Eliminar" aria-label="Eliminar">@include('cms-core::components.icons.trash')</button>
                                    </form>
                                </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-secondary py-4">Ainda não existem redirecionamentos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $redirects->links() }}</div>
    </div>
@endsection
