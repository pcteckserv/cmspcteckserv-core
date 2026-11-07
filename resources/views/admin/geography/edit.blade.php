@extends('cms-core::admin.layouts.app', ['title' => 'Editar país e regiões'])
@section('content')
<div class="d-flex justify-content-between mb-4"><h1 class="h3">Editar país e regiões · {{ $country }}</h1><a class="btn btn-outline-secondary" href="{{ route('admin.geography.index') }}">Voltar à lista</a></div>
@if(session('cms_geography_success'))<div class="alert alert-success">{{ session('cms_geography_success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="post" action="{{ route('admin.geography.update', $country) }}" class="bg-white border rounded-2 p-4">
    @csrf @method('PUT')
    <input type="hidden" name="_revision" value="{{ old('_revision', $entry['revision']) }}">
    <input type="hidden" name="_regions_present" value="1">
    <fieldset @disabled(!auth()->user()->can('core.geography.update'))>
        <label class="form-label" for="geography-name">Nome do país</label>
        <input class="form-control mb-4" id="geography-name" name="name" value="{{ old('name', $entry['name']) }}" maxlength="100" required>
        <h2 class="h5">Regiões</h2>
        @foreach((array) old('regions', $entry['regions']) as $index => $region)
            <label class="form-label" for="geography-region-{{ $index }}">{{ $region['code'] ?? '' }}</label>
            <input type="hidden" name="regions[{{ $index }}][code]" value="{{ $region['code'] ?? '' }}">
            <input class="form-control mb-3" id="geography-region-{{ $index }}" name="regions[{{ $index }}][name]" value="{{ $region['name'] ?? '' }}" maxlength="80" required>
        @endforeach
        @can('core.geography.update')
            <label class="form-label" for="geography-new-region">Adicionar região (opcional)</label>
            <input class="form-control mb-3" id="geography-new-region" name="new_region" maxlength="80">
            <button class="btn btn-primary" type="submit">Guardar alterações</button>
        @endcan
    </fieldset>
</form>
@endsection
