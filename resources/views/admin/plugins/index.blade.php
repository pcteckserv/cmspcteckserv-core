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

    @can('plugins.install')
        <div class="bg-white border rounded-2 p-4 mb-4">
            <h2 class="h5 mb-3">Instalar plugin</h2>

            <form method="POST" action="{{ route('admin.plugins.install') }}" class="row g-3">
                @csrf

                <div class="col-12 col-lg-6">
                    <label class="form-label" for="package">Package Composer</label>
                    <input
                        class="form-control @error('package') is-invalid @enderror"
                        id="package"
                        name="package"
                        type="text"
                        value="{{ old('package') }}"
                        placeholder="pcteckserv/cms-contact-forms"
                        required
                    >
                    @error('package')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-lg-3">
                    <label class="form-label" for="version_constraint">Versão</label>
                    <input
                        class="form-control @error('version_constraint') is-invalid @enderror"
                        id="version_constraint"
                        name="version_constraint"
                        type="text"
                        value="{{ old('version_constraint') }}"
                        placeholder="*@dev ou ^1.0"
                    >
                    @error('version_constraint')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-lg-3">
                    <label class="form-label" for="slug">Identificador</label>
                    <input
                        class="form-control @error('slug') is-invalid @enderror"
                        id="slug"
                        name="slug"
                        type="text"
                        value="{{ old('slug') }}"
                        placeholder="contact-forms"
                    >
                    @error('slug')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-lg-3">
                    <label class="form-label" for="label">Nome visível</label>
                    <input
                        class="form-control @error('label') is-invalid @enderror"
                        id="label"
                        name="label"
                        type="text"
                        value="{{ old('label') }}"
                        placeholder="Formulários de contacto"
                    >
                    @error('label')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label class="form-label" for="description">Descrição</label>
                    <input
                        class="form-control @error('description') is-invalid @enderror"
                        id="description"
                        name="description"
                        type="text"
                        value="{{ old('description') }}"
                    >
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-lg-4">
                    <label class="form-label" for="repository_type">Tipo de repositório</label>
                    <select class="form-select @error('repository_type') is-invalid @enderror" id="repository_type" name="repository_type">
                        <option value="">Usar repositórios já configurados</option>
                        <option value="path" @selected(old('repository_type') === 'path')>Pasta local</option>
                        <option value="vcs" @selected(old('repository_type') === 'vcs')>Git/VCS</option>
                    </select>
                    @error('repository_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-lg-8">
                    <label class="form-label" for="repository_url">Caminho ou URL do repositório</label>
                    <input
                        class="form-control @error('repository_url') is-invalid @enderror"
                        id="repository_url"
                        name="repository_url"
                        type="text"
                        value="{{ old('repository_url') }}"
                        placeholder="../plugins/cmspcteckserv-formularios-de-contacto"
                    >
                    @error('repository_url')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label class="form-label" for="provider">Service provider</label>
                    <input
                        class="form-control @error('provider') is-invalid @enderror"
                        id="provider"
                        name="provider"
                        type="text"
                        value="{{ old('provider') }}"
                        placeholder="Pcteckserv\CmsContactForms\CmsContactFormsServiceProvider"
                    >
                    @error('provider')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <button class="btn btn-primary" type="submit" @disabled(! $pluginsEnabled)>Instalar plugin</button>
                </div>
            </form>
        </div>
    @endcan

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
