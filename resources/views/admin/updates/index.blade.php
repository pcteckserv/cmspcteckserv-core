@extends('cms-core::admin.layouts.app', ['title' => 'Atualizações'])

@section('content')
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Atualizações</h1>
            <p class="text-secondary mb-0">Estado dos packages CMS instalados neste site e versões disponíveis no repositório.</p>
        </div>

        <div class="d-flex align-items-start gap-2">
            <span @class(['badge', 'text-bg-success' => $updatesEnabled, 'text-bg-secondary' => ! $updatesEnabled])>
                {{ $updatesEnabled ? 'Ativo' : 'Desativado' }}
            </span>
            <span class="badge text-bg-light border">Canal {{ $channel }}</span>
        </div>
    </div>

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
                    </tr>
                </thead>
                <tbody>
                    @forelse ($packages as $package)
                        <tr>
                            <td class="fw-semibold">{{ $package->name }}</td>
                            <td>{{ $package->installedVersion ?? '-' }}</td>
                            <td>{{ $package->availableVersion ?? 'Não verificada' }}</td>
                            <td>{{ $package->checkedAt ?? '-' }}</td>
                            <td>
                                @if ($package->hasUpdate())
                                    <span class="badge text-bg-warning">Update disponível</span>
                                @else
                                    <span class="badge text-bg-success">Atualizado</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-secondary text-center py-4" colspan="5">Nenhum package CMS registado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
