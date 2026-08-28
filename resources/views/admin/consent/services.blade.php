@extends('cms-core::admin.layouts.app', ['title' => 'Serviços de consentimento'])

@section('content')
<h1 class="h3 mb-3">Serviços</h1>
<div class="card">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead><tr><th>Serviço</th><th>Fornecedor</th><th>Categoria</th><th>Revisão</th><th>Confiança</th><th></th></tr></thead>
            <tbody>
            @forelse($services as $service)
                <tr>
                    <td>{{ $service->name }}</td>
                    <td>{{ $service->provider }}</td>
                    <td>{{ $service->category?->name ?? 'Por definir' }}</td>
                    <td>{{ $service->review_status }}</td>
                    <td>{{ $service->confidence }}%</td>
                    <td><a class="btn btn-outline-primary btn-sm" href="{{ route('admin.consent.services.show', $service) }}">Ver</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-secondary">Ainda não existem serviços registados.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $services->links() }}
@endsection
