@extends('cms-core::admin.layouts.app', ['title' => 'Atualizações'])

@section('content')
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Atualizações</h1>
            <p class="text-secondary mb-0">Estado dos packages CMS instalados neste site e novas versões disponíveis no repositório.</p>
        </div>

        <div class="d-flex align-items-start gap-2">
            <span @class(['badge', 'text-bg-success' => $updatesEnabled, 'text-bg-secondary' => ! $updatesEnabled])>
                {{ $updatesEnabled ? 'Ativo' : 'Desativado' }}
            </span>
            <span class="badge text-bg-light border">Canal {{ $channel }}</span>
        </div>
    </div>

    @if (session('cms_update_success'))
        <div class="alert alert-success">{{ session('cms_update_success') }}</div>
    @endif

    @if (session('cms_update_error'))
        <div class="alert alert-danger">{{ session('cms_update_error') }}</div>
    @endif

    @if ($queueConnection === 'sync')
        <div class="alert alert-warning">
            A queue está configurada como sync. Para atualizar em segundo plano, configure uma queue assíncrona e mantenha um worker ativo.
        </div>
    @endif

    <div class="bg-white border rounded-2">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Package</th>
                        <th scope="col">Versão instalada</th>
                        <th scope="col">Versão disponível</th>
                        <th scope="col">Última verificação</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="text-end">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($packages as $package)
                        @php($status = $statuses[$package->name] ?? null)
                        <tr>
                            <td class="fw-semibold">{{ $package->name }}</td>
                            <td>{{ $package->installedVersion ?? '-' }}</td>
                            <td>{{ $package->availableVersion ?? 'Não verificada' }}</td>
                            <td>{{ $package->formattedCheckedAt() }}</td>
                            <td>
                                @if (($status['state'] ?? null) === 'queued')
                                    <span class="badge text-bg-info">Na fila</span>
                                @elseif (($status['state'] ?? null) === 'running')
                                    <span class="badge text-bg-primary">Em execução</span>
                                @elseif (($status['state'] ?? null) === 'failed')
                                    <span class="badge text-bg-danger">Falhou</span>
                                @elseif (($status['state'] ?? null) === 'succeeded')
                                    <span class="badge text-bg-success">Concluída</span>
                                @elseif ($package->hasUpdate())
                                    <span class="badge text-bg-warning">Update disponível</span>
                                @else
                                    <span class="badge text-bg-success">Atualizado</span>
                                @endif

                                @if ($status)
                                    <div class="small text-secondary mt-1">
                                        {{ $status['message'] }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-end">
                                @if (in_array($status['state'] ?? null, ['queued', 'running'], true))
                                    <button class="btn btn-outline-secondary btn-sm" type="button" disabled>A processar</button>
                                @elseif ($package->hasUpdate())
                                    <form method="POST" action="{{ route('admin.updates.run', ['package' => $package->name]) }}">
                                        @csrf
                                        <button class="btn btn-primary btn-sm" type="submit">Atualizar</button>
                                    </form>
                                @else
                                    <button class="btn btn-outline-secondary btn-sm" type="button" disabled>Sem update</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-secondary text-center py-4" colspan="6">Nenhum package CMS registado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
