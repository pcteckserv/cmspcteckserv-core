@extends('cms-core::admin.layouts.app', ['title' => 'Países e regiões'])
@section('content')
<h1 class="h3 mb-4">Países e regiões</h1>
@if(session('cms_geography_success'))<div class="alert alert-success">{{ session('cms_geography_success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@can('core.geography.update')
<form method="post" action="{{ route('admin.geography.store') }}" class="bg-white border rounded-2 p-4 mb-4">
    @csrf
    <label class="form-label" for="geography-countries">Países a importar</label>
    <select class="form-select mb-3" id="geography-countries" name="countries[]" multiple size="10">
        @foreach($countries as $code => $label)<option value="{{ $code }}" @selected(in_array($code, (array) old('countries', []), true))>{{ $label }}</option>@endforeach
    </select>
    <button class="btn btn-primary" type="submit">Importar regiões</button>
</form>
@endcan
<div class="bg-white border rounded-2 p-4">
    <h2 class="h5">Países com dados importados</h2>
    <ul class="list-group list-group-flush">
    @forelse($imported as $code => $entry)
        <li class="list-group-item d-flex justify-content-between"><a href="{{ route('admin.geography.edit', $code) }}">{{ $entry['name'] }}</a><span>{{ count($entry['regions']) }} regiões</span></li>
    @empty<li class="list-group-item">Ainda não foram importadas regiões.</li>@endforelse
    </ul>
</div>
@endsection
