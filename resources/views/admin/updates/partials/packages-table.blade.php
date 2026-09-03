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
                        <td class="text-secondary text-center py-4" colspan="6">Nenhum package registado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
