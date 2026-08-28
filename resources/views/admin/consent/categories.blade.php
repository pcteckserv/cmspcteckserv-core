@extends('cms-core::admin.layouts.app', ['title' => 'Categorias de consentimento'])

@section('content')
<h1 class="h3 mb-3">Categorias</h1>
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@foreach($categories as $category)
<form method="POST" action="{{ route('admin.consent.categories.update', $category) }}" class="card mb-3">
    @csrf
    @method('PUT')
    <div class="card-body row g-3">
        <div class="col-md-3"><label class="form-label">Chave</label><input class="form-control" value="{{ $category->key }}" disabled></div>
        <div class="col-md-3"><label class="form-label">Nome</label><input class="form-control" name="name" value="{{ old('name', $category->name) }}"></div>
        <div class="col-md-2"><label class="form-label">Ordem</label><input class="form-control" type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order) }}"></div>
        <div class="col-md-2"><label class="form-label">Cor</label><input class="form-control" name="color" value="{{ old('color', $category->color) }}"></div>
        <div class="col-md-2"><label class="form-label">Ícone</label><input class="form-control" name="icon" value="{{ old('icon', $category->icon) }}"></div>
        <div class="col-md-6"><label class="form-label">Descrição interna</label><textarea class="form-control" name="description">{{ old('description', $category->description) }}</textarea></div>
        <div class="col-md-6"><label class="form-label">Texto público</label><textarea class="form-control" name="public_text">{{ old('public_text', $category->public_text) }}</textarea></div>
        <div class="col-12 d-flex gap-3">
            <label class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($category->is_active)> Ativa</label>
            <label class="form-check"><input class="form-check-input" type="checkbox" name="is_required" value="1" @checked($category->is_required)> Obrigatória</label>
        </div>
    </div>
    <div class="card-footer"><button class="btn btn-primary btn-sm">Guardar</button></div>
</form>
@endforeach
@endsection
