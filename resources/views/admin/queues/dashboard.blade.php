@extends('cms-core::admin.layouts.app', ['title' => 'Tarefas em segundo plano'])

@section('content')
<div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Tarefas em segundo plano</h1>
        <p class="text-secondary mb-0">Monitorização da queue Laravel, jobs pendentes, falhados e batches.</p>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-start">
        <span class="badge text-bg-light border">Ligação {{ $connection }}</span>
        <span class="badge text-bg-light border">Driver {{ $driver }}</span>
    </div>
</div>

@if(session('queue_success'))
    <div class="alert alert-success">{{ session('queue_success') }}</div>
@endif

@if(session('queue_error'))
    <div class="alert alert-danger">{{ session('queue_error') }}</div>
@endif

@if(session('queue_output'))
    <div class="bg-dark text-white rounded-2 p-3 mb-4">
        <div class="small text-white-50 mb-2">Output</div>
        <pre class="mb-0 text-white" style="white-space: pre-wrap;">{{ session('queue_output') }}</pre>
    </div>
@endif

@if(! $supports_database_monitoring)
    <div class="alert alert-warning">
        O driver atual não permite monitorização detalhada através da tabela <code>{{ $jobs_table }}</code>.
        O painel continua a mostrar falhas registadas e comandos operacionais quando disponíveis.
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card"><div class="card-body"><div class="text-secondary">Pendentes</div><strong class="fs-4">{{ $pending_jobs }}</strong></div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body"><div class="text-secondary">Em execução</div><strong class="fs-4">{{ $reserved_jobs }}</strong></div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body"><div class="text-secondary">Falhados</div><strong class="fs-4">{{ $failed_jobs }}</strong></div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body"><div class="text-secondary">Batches ativos</div><strong class="fs-4">{{ $batches }}</strong></div></div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header fw-semibold">Operações</div>
    <div class="card-body d-flex flex-wrap gap-2">
        @can('queues.manage')
            <form method="POST" action="{{ route('admin.queues.work-once') }}">
                @csrf
                <button class="btn btn-primary">Processar agora</button>
            </form>
            <form method="POST" action="{{ route('admin.queues.restart') }}">
                @csrf
                <button class="btn btn-outline-primary">Reiniciar workers</button>
            </form>
            @if($failed_jobs > 0)
                <form method="POST" action="{{ route('admin.queues.failed.retry-all') }}">
                    @csrf
                    <button class="btn btn-outline-danger">Reprocessar falhados</button>
                </form>
            @endif
        @else
            <span class="text-secondary">Sem permissões para gerir a queue.</span>
        @endcan
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">Localhost</div>
            <div class="card-body">
                <p class="text-secondary">Para workers manuais durante desenvolvimento, deixa este comando aberto num terminal:</p>
                <pre class="bg-light border rounded-2 p-3 mb-0"><code>{{ $recommended_local_command }}</code></pre>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">Produção com Supervisor</div>
            <div class="card-body">
                <p class="text-secondary">O Supervisor deve manter workers sempre ativos. Comando recomendado:</p>
                <pre class="bg-light border rounded-2 p-3 mb-0"><code>{{ $recommended_supervisor_command }}</code></pre>
            </div>
        </div>
    </div>
</div>

@if(count($queues) > 0)
    <div class="card mb-4">
        <div class="card-header fw-semibold">Queues com trabalhos pendentes</div>
        <table class="table mb-0">
            <thead><tr><th>Queue</th><th>Total</th></tr></thead>
            <tbody>
                @foreach($queues as $queue)
                    <tr><td>{{ $queue['queue'] }}</td><td>{{ $queue['total'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<div class="card mb-4">
    <div class="card-header fw-semibold">Jobs recentes</div>
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead><tr><th>ID</th><th>Job</th><th>Queue</th><th>Tentativas</th><th>Estado</th><th>Criado</th><th>Disponível</th></tr></thead>
            <tbody>
                @forelse($recent_jobs as $job)
                    <tr>
                        <td>{{ $job['id'] }}</td>
                        <td><code>{{ $job['name'] }}</code></td>
                        <td>{{ $job['queue'] }}</td>
                        <td>{{ $job['attempts'] }}</td>
                        <td>{{ $job['reserved'] ? 'Em execução' : 'Pendente' }}</td>
                        <td>{{ $job['created_at'] }}</td>
                        <td>{{ $job['available_at'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-secondary text-center py-4">Não existem jobs pendentes.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header fw-semibold">Jobs falhados recentes</div>
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead><tr><th>ID</th><th>Job</th><th>Queue</th><th>Falhou em</th><th>Erro</th><th class="text-end">Ações</th></tr></thead>
            <tbody>
                @forelse($recent_failed_jobs as $job)
                    <tr>
                        <td>{{ $job['uuid'] }}</td>
                        <td><code>{{ $job['name'] }}</code></td>
                        <td>{{ $job['queue'] }}</td>
                        <td>{{ $job['failed_at'] }}</td>
                        <td class="text-secondary">{{ $job['exception'] }}</td>
                        <td class="text-end">
                            @can('queues.manage')
                                <div class="d-flex justify-content-end gap-2">
                                    <form method="POST" action="{{ route('admin.queues.failed.retry', $job['uuid']) }}">
                                        @csrf
                                        <button class="btn btn-outline-primary btn-sm">Reprocessar</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.queues.failed.forget', $job['uuid']) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm">Remover</button>
                                    </form>
                                </div>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-secondary text-center py-4">Não existem jobs falhados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(count($recent_batches) > 0)
    <div class="card">
        <div class="card-header fw-semibold">Batches recentes</div>
        <table class="table mb-0">
            <thead><tr><th>Nome</th><th>Total</th><th>Pendentes</th><th>Falhados</th><th>Criado</th><th>Concluído</th></tr></thead>
            <tbody>
                @foreach($recent_batches as $batch)
                    <tr>
                        <td>{{ $batch['name'] }}</td>
                        <td>{{ $batch['total_jobs'] }}</td>
                        <td>{{ $batch['pending_jobs'] }}</td>
                        <td>{{ $batch['failed_jobs'] }}</td>
                        <td>{{ $batch['created_at'] }}</td>
                        <td>{{ $batch['finished_at'] ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
