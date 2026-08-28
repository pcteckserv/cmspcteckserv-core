@extends('cms-core::admin.layouts.app', ['title' => $service->name])

@section('content')
<h1 class="h3 mb-3">{{ $service->name }}</h1>
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<form method="POST" action="{{ route('admin.consent.services.update', $service) }}" class="card mb-4">
    @csrf
    @method('PUT')
    <div class="card-body row g-3">
        <div class="col-md-4"><label class="form-label">Nome</label><input class="form-control" name="name" value="{{ old('name', $service->name) }}"></div>
        <div class="col-md-4"><label class="form-label">Fornecedor</label><input class="form-control" name="provider" value="{{ old('provider', $service->provider) }}"></div>
        <div class="col-md-4"><label class="form-label">Categoria</label><select class="form-select" name="category_id"><option value="">Por definir</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected($service->category_id === $category->id)>{{ $category->name }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">Estado</label><select class="form-select" name="status"><option @selected($service->status === 'active') value="active">Ativo</option><option @selected($service->status === 'inactive') value="inactive">Inativo</option><option @selected($service->status === 'ignored') value="ignored">Ignorado</option></select></div>
        <div class="col-md-4"><label class="form-label">Revisão</label><select class="form-select" name="review_status"><option value="confirmed">Confirmado</option><option value="suggested">Sugerido</option><option value="requires_review">Requer revisão</option><option value="ignored">Ignorado</option></select></div>
        <div class="col-md-4 d-flex align-items-end"><label class="form-check"><input class="form-check-input" type="checkbox" name="requires_consent" value="1" @checked($service->requires_consent)> Requer consentimento</label></div>
        <div class="col-md-6"><label class="form-label">Descrição</label><textarea class="form-control" name="description">{{ old('description', $service->description) }}</textarea></div>
        <div class="col-md-6"><label class="form-label">Finalidade</label><textarea class="form-control" name="purpose">{{ old('purpose', $service->purpose) }}</textarea></div>
    </div>
    <div class="card-footer"><button class="btn btn-primary">Guardar</button></div>
</form>
<h2 class="h5">Tecnologias detetadas</h2>
<ul class="list-group">
@foreach($service->technologies as $technology)
    <li class="list-group-item"><strong>{{ $technology->type }}</strong> {{ $technology->name }} <span class="text-secondary">{{ $technology->domain }}</span></li>
@endforeach
</ul>
@endsection
