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

    @if ($pluginRepositoryError)
        <div class="alert alert-warning">{{ $pluginRepositoryError }}</div>
    @endif

    @can('plugins.install')
        <h2 class="h5">Plugins disponíveis para instalar</h2>
        <div class="bg-white border rounded-2 mb-4">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Plugin</th>
                            <th scope="col">Package</th>
                            <th scope="col">Pasta</th>
                            <th scope="col">Versão disponível</th>
                            <th scope="col" class="text-end">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($availablePlugins->reject(fn ($available) => $plugins->contains(fn ($plugin) => $plugin->package === $available->package && $plugin->installed_version !== null)) as $availablePlugin)
                            @php
                                $isInstalled = $plugins->contains(fn ($plugin) => $plugin->slug === $availablePlugin->slug && $plugin->installed_version !== null);
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $availablePlugin->label }}</div>
                                    @if ($availablePlugin->description)
                                        <div class="small text-secondary">{{ $availablePlugin->description }}</div>
                                    @endif
                                </td>
                                <td>{{ $availablePlugin->package }}</td>
                                <td>{{ $availablePlugin->directory }}</td>
                                <td>{{ $availablePlugin->version ?? 'Por verificar' }}@if(!$availablePlugin->compatible())<div class="small text-warning">Requer Core {{ $availablePlugin->coreConstraint }}</div>@endif</td>
                                <td class="text-end">
                                    @if ($isInstalled)
                                        <span class="badge text-bg-success">Instalado</span>
                                    @else
                                        <form method="POST" action="{{ route('admin.plugins.install') }}">
                                            @csrf
                                            <input type="hidden" name="plugin" value="{{ $availablePlugin->slug }}">
                                            <button class="btn btn-primary btn-sm" type="submit" @disabled(! $pluginsEnabled || !$availablePlugin->compatible())>Instalar</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-secondary text-center py-4" colspan="5">Nenhum plugin adicional disponível para instalar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endcan

    <h2 class="h5">Plugins no site</h2>
    <div class="bg-white border rounded-2">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Plugin</th>
                        <th scope="col">Package</th>
                        <th scope="col">Versão instalada</th>
                        <th scope="col">Versão disponível</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="text-end">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($plugins->filter(fn ($item) => $item->installed_version !== null || !auth()->user()?->can('plugins.install') || !$availablePlugins->contains('package', $item->package)) as $plugin)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $plugin->label }}</div>
                                @if ($plugin->description)
                                    <div class="small text-secondary">{{ $plugin->description }}</div>
                                @endif
                            </td>
                            <td>{{ $plugin->package }}</td>
                            <td>{{ $plugin->installed_version ?? 'Não instalado' }}</td>
                            <td>{{ $availablePlugins->firstWhere('package', $plugin->package)?->version ?? 'Por verificar' }}</td>
                            <td>
                                @if ($plugin->installed_version === null)
                                    <span class="badge text-bg-secondary">Não instalado</span>
                                @elseif ($plugin->isEnabled())
                                    <span class="badge text-bg-success">Ativo</span>
                                @else
                                    <span class="badge text-bg-secondary">Desativado</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($plugin->installed_version === null)
                                    <span class="text-secondary small">Instalação indisponível</span>
                                @elseif ($plugin->isEnabled())
                                    <form method="POST" action="{{ route('admin.plugins.disable', $plugin->slug) }}">
                                        @csrf
                                        @method('PUT')
                                        <button class="btn btn-outline-secondary btn-sm" type="submit">Desativar</button>
                                    </form>
                                @else
                                    <div class="d-flex justify-content-end align-items-center gap-2">
                                    <form method="POST" action="{{ route('admin.plugins.enable', $plugin->slug) }}">
                                        @csrf
                                        @method('PUT')
                                        <button class="btn btn-primary btn-sm" type="submit">Ativar</button>
                                    </form>
                                    @can('plugins.manage')
                                        @can('plugins.install')
                                            @if ($plugin->installed_version !== null)
                                                <form method="POST" action="{{ route('admin.plugins.destroy', $plugin->slug) }}" onsubmit="return confirm('Eliminar este plugin? Os dados existentes serão preservados.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-outline-danger btn-sm" type="submit" title="Eliminar plugin" aria-label="Eliminar plugin {{ $plugin->label }}">
                                                        @include('cms-core::components.icons.trash')
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                    @endcan
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-secondary text-center py-4" colspan="6">Nenhum plugin instalado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
