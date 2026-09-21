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

    @php
        $pluginPackageNames = collect($pluginPackages ?? []);
        $starterPackageName = \Pcteckserv\CmsCore\Updates\StarterPackage::NAME;
        $corePackages = $packages->reject(fn ($package) => $pluginPackageNames->contains($package->name) || $package->name === $starterPackageName)->values();
        $starterPackages = $packages->filter(fn ($package) => $package->name === $starterPackageName)->values();
        $pluginPackagesList = $packages->filter(fn ($package) => $pluginPackageNames->contains($package->name))->values();
    @endphp

    <h2 class="h5 mb-3">Core</h2>
    @include('cms-core::admin.updates.partials.packages-table', ['packages' => $corePackages, 'statuses' => $statuses])

    <h2 class="h5 mt-4 mb-3">Starter</h2>
    @include('cms-core::admin.updates.partials.packages-table', ['packages' => $starterPackages, 'statuses' => $statuses])

    <h2 class="h5 mt-4 mb-3">Plugins</h2>
    @include('cms-core::admin.updates.partials.packages-table', ['packages' => $pluginPackagesList, 'statuses' => $statuses])
@endsection
