@extends('cms-core::admin.layouts.app', ['title' => 'Scanner de consentimentos'])

@section('content')
@if($hasActiveScans)
    <meta http-equiv="refresh" content="5">
@endif

<h1 class="h3 mb-3">Scanner</h1>

@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

@if($hasActiveScans)
    <div class="alert alert-info d-flex justify-content-between align-items-center gap-3">
        <div>
            <strong>Análise em acompanhamento.</strong>
            Esta página atualiza automaticamente de 5 em 5 segundos enquanto houver scans pendentes ou em execução.
        </div>
        <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
    </div>
@endif

@if($queueConnection !== 'sync')
    <div class="alert alert-warning">
        As análises em segundo plano dependem da queue <strong>{{ $queueConnection }}</strong>.
        Se ficarem em <strong>Pendente</strong>, inicia um worker com <code>php artisan queue:work</code> ou usa <strong>Executar agora</strong>.
    </div>
@endif

<form method="POST" action="{{ route('admin.consent.scans.store') }}" class="card mb-4">
    @csrf
    <div class="card-body">
        <label class="form-label">URLs adicionais, uma por linha</label>
        <textarea class="form-control" name="urls" rows="4"></textarea>
        <div class="form-text">Sem URLs adicionais, o scanner tenta descobrir automaticamente as rotas públicas analisáveis.</div>
    </div>
    <div class="card-footer d-flex flex-wrap gap-2">
        <button class="btn btn-primary" name="mode" value="queue">Executar em segundo plano</button>
        <button class="btn btn-outline-primary" name="mode" value="now">Executar agora</button>
    </div>
</form>

<div class="card">
    <table class="table mb-0 align-middle">
        <thead>
            <tr>
                <th>Data</th>
                <th>Estado</th>
                <th>Progresso</th>
                <th>Tecnologias</th>
                <th>Alterações</th>
                <th>Detalhe</th>
            </tr>
        </thead>
        <tbody>
            @forelse($scans as $scan)
                <tr>
                    <td>{{ $scan->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        @if($scan->status === 'completed')
                            <span class="badge text-bg-success">Concluída</span>
                        @elseif($scan->status === 'running')
                            <span class="badge text-bg-primary">A analisar</span>
                        @elseif($scan->status === 'failed')
                            <span class="badge text-bg-danger">Falhada</span>
                        @else
                            <span class="badge text-bg-warning">Pendente</span>
                        @endif
                    </td>
                    <td>{{ $scan->pages_scanned }} / {{ count($scan->urls ?? []) }} páginas</td>
                    <td>{{ $scan->technologies_found }}</td>
                    <td>{{ $scan->changes_found }}</td>
                    <td>
                        @if($scan->status === 'pending')
                            A aguardar processamento pela queue.
                        @elseif($scan->status === 'running')
                            A recolher scripts, iframes e storage.
                        @elseif($scan->status === 'failed')
                            <span class="text-danger">{{ $scan->error_log ?: 'Erro não especificado.' }}</span>
                        @else
                            Terminou em {{ $scan->finished_at?->format('d/m/Y H:i:s') }}.
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-secondary text-center py-4">Ainda não existem análises.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $scans->links() }}
@endsection
