@extends('cms-core::admin.layouts.app', ['title' => 'Atualizações'])

@section('content')
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Atualizações</h1>
            <p class="text-secondary mb-0">Versões instaladas, verificações e atualizações do Core, Starter e plugins ativos.</p>
        </div>

        <div class="d-flex align-items-start gap-2">
            <form method="GET" action="{{ route('admin.updates.index') }}">
                <button class="btn btn-outline-primary btn-sm" type="submit" @disabled(!$updatesEnabled)>Verificar atualizações</button>
            </form>
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

    @php
        $pluginPackageNames = collect($pluginPackages ?? []);
        $starterPackageName = \Pcteckserv\CmsCore\Updates\StarterPackage::NAME;
        $corePackages = $packages->filter(fn ($package) => $package->name === 'pcteckserv/cms-core')->values();
        $starterPackages = $packages->filter(fn ($package) => $package->name === $starterPackageName)->values();
        $activePluginLabels = \Pcteckserv\CmsCore\Models\InstalledPlugin::query()->where('status', 'enabled')->whereNotNull('installed_version')->pluck('label','package');
        $activePluginPackages = $activePluginLabels->keys();
        $pluginPackagesList = $packages->filter(fn ($package) => $activePluginPackages->contains($package->name))->values();
    @endphp

    <h2 class="h5 mb-3">Core</h2>
    <p class="small text-secondary">A atualização valida a compatibilidade antes de instalar e preserva as versões do Laravel e dos plugins. Mudanças de versão principal exigem migração.</p>
    @include($updatesTableView ?? 'cms-core::admin.updates.partials.packages-table', ['packages' => $corePackages, 'statuses' => $statuses])

    <h2 class="h5 mt-4 mb-3">Starter</h2>
    @include($updatesTableView ?? 'cms-core::admin.updates.partials.packages-table', ['packages' => $starterPackages, 'statuses' => $statuses])

    <h2 class="h5 mt-4 mb-3">Plugins ativos instalados</h2>
    @include($updatesTableView ?? 'cms-core::admin.updates.partials.packages-table', ['packages' => $pluginPackagesList, 'statuses' => $statuses])
@endsection
