<div class="bg-white border rounded-2">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th scope="col">Package</th>
                    <th scope="col">Versão instalada</th>
                    <th scope="col">Versão disponível</th>
                    <th scope="col">Última tentativa de verificação</th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="text-end">Ação</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($packages as $package)
                    @php($status = $statuses[$package->name] ?? null)
                    <tr data-update-package="{{ $package->name }}">
                        <td class="fw-semibold">{{ \Pcteckserv\CmsCore\Updates\StarterPackage::is($package->name) ? \Pcteckserv\CmsCore\Updates\StarterPackage::displayName().' · Starter' : $package->name }}
                            @if(\Pcteckserv\CmsCore\Updates\StarterPackage::is($package->name) && ($repository = \Pcteckserv\CmsCore\Updates\StarterPackage::repositoryLabel()))<div class="small text-secondary fw-normal">{{ $repository }}</div>@endif
                        </td>
                        <td>{{ $package->installedVersion ?? '-' }}</td>
                        <td>{{ $package->availableVersion ?? 'Não verificada' }}</td>
                        <td>{{ config('cms-core.updates.enabled', true) ? $package->formattedCheckedAt() : 'Verificação desativada' }}</td>
                        <td>
                            @if ($package->installedVersion === null)
                                <span class="badge text-bg-secondary">Não instalado</span>
                            @elseif (($status['state'] ?? null) === 'queued')
                                <span class="badge text-bg-info">Na fila</span>
                            @elseif (($status['state'] ?? null) === 'running')
                                <span class="badge text-bg-primary">Em execução</span>
                            @elseif (($status['state'] ?? null) === 'failed')
                                <span class="badge text-bg-danger">Falhou</span>
                            @elseif ($package->availableVersion === null)
                                <span class="badge text-bg-secondary">Versão por verificar</span>
                            @elseif ($package->hasUpdate())
                                <span class="badge text-bg-warning">Atualização disponível</span>
                            @else
                                <span class="badge text-bg-success">Sem atualização conhecida</span>
                            @endif

                            @if ($status)
                                <div class="small text-secondary mt-1">
                                    {{ $status['message'] }}
                                </div>
                            @endif
                        </td>
                        <td class="text-end">
                            @if (!config('cms-core.updates.enabled', true))
                                <button class="btn btn-outline-secondary btn-sm" type="button" disabled>Desativado</button>
                            @elseif ($package->name === 'pcteckserv/cms-core' && !config('cms-core.updates.allow_core_updates', true))
                                <button class="btn btn-outline-secondary btn-sm" type="button" disabled>Atualização manual</button>
                            @elseif (in_array($status['state'] ?? null, ['queued', 'running'], true))
                                <button class="btn btn-outline-secondary btn-sm" type="button" disabled>A processar</button>
                            @elseif ($package->availableVersion === null)
                                <button class="btn btn-outline-secondary btn-sm" type="button" disabled>Versão por verificar</button>
                            @elseif ($package->hasUpdate())
                                <form method="POST" action="{{ route('admin.updates.run', ['package' => $package->name]) }}">
                                    @csrf
                                    <button class="btn btn-primary btn-sm" type="submit">Atualizar</button>
                                </form>
                            @else
                                <button class="btn btn-outline-secondary btn-sm" type="button" disabled>Sem atualização</button>
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
