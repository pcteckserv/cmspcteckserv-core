@extends('cms-core::admin.layouts.app', ['title' => 'Plugins'])

@section('content')
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Plugins</h1>
            <p class="text-secondary mb-0">Gestão dos plugins opcionais instalados neste CMS.</p>
        </div>

        <span @class(['badge align-self-start', 'text-bg-success' => $pluginsEnabled, 'text-bg-secondary' => ! $pluginsEnabled])>
            {{ $pluginsEnabled ? 'Ativo' : 'Desativado' }}
        </span>
    </div>

    @if (session('cms_plugin_success'))
        <div class="alert alert-success">{{ session('cms_plugin_success') }}</div>
    @endif

    @if (session('cms_plugin_error'))
        <div class="alert alert-danger">{{ session('cms_plugin_error') }}</div>
    @endif

    <div class="bg-white border rounded-2">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Plugin</th>
                        <th scope="col">Package</th>
                        <th scope="col">Versão instalada</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="text-end">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($plugins as $plugin)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $plugin->label }}</div>
                                @if ($plugin->description)
                                    <div class="small text-secondary">{{ $plugin->description }}</div>
                                @endif
                            </td>
                            <td>{{ $plugin->package }}</td>
                            <td>{{ $plugin->installed_version ?? 'Não instalado' }}</td>
                            <td>
                                @if ($plugin->isEnabled())
                                    <span class="badge text-bg-success">Ativo</span>
                                @else
                                    <span class="badge text-bg-secondary">Desativado</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($plugin->isEnabled())
                                    <form method="POST" action="{{ route('admin.plugins.disable', $plugin->slug) }}">
                                        @csrf
                                        @method('PUT')
                                        <button class="btn btn-outline-secondary btn-sm" type="submit">Desativar</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.plugins.enable', $plugin->slug) }}">
                                        @csrf
                                        @method('PUT')
                                        <button class="btn btn-primary btn-sm" type="submit">Ativar</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-secondary text-center py-4" colspan="5">Nenhum plugin configurado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
