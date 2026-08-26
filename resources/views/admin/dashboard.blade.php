@extends('cms-core::admin.layouts.app', ['title' => 'Painel de Administração'])

@section('content')
    @if ($maintenanceIsActive ?? false)
        <div class="alert alert-warning d-flex flex-column flex-lg-row align-items-lg-center gap-3 mb-4">
            <div>
                <strong>O site encontra-se atualmente em Modo de Manutenção.</strong>
                <div class="small">Template ativo: {{ $maintenance['template_name'] ?? 'Sem template' }}.</div>
            </div>
            <div class="d-flex gap-2 ms-lg-auto">
                @can('maintenance.view')
                    <a class="btn btn-outline-dark btn-sm" href="{{ route('admin.maintenance.edit') }}">Gerir manutenção</a>
                @endcan
                @can('maintenance.preview')
                    <a class="btn btn-outline-dark btn-sm" href="{{ route('admin.maintenance.preview') }}" target="_blank" rel="noopener">Ver página</a>
                @endcan
            </div>
        </div>
    @endif

    <div class="bg-white border rounded-2 p-4">
        <h1 class="h3 mb-3">Painel de Administração</h1>
        <p class="mb-1">Bem-vindo, {{ auth()->user()->name }}.</p>
        <p class="text-secondary mb-0">Sessão iniciada com o email {{ auth()->user()->email }}.</p>
    </div>
@endsection
