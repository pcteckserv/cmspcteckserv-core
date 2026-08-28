@extends('cms-core::admin.layouts.app', ['title' => 'Configuração de consentimentos'])

@section('content')
<h1 class="h3 mb-3">Banner e textos</h1>
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<form method="POST" action="{{ route('admin.consent.settings.update') }}" class="card">
    @csrf
    @method('PUT')
    <div class="card-body">
        <label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="banner_enabled" value="1" @checked($settings->banner_enabled)> Banner ativo</label>
        <label class="form-check mb-4"><input class="form-check-input" type="checkbox" name="server_records_enabled" value="1" @checked($settings->server_records_enabled)> Guardar histórico técnico no servidor</label>
        <div class="row g-3">
            @foreach($defaultTexts as $key => $value)
                <div class="col-md-6">
                    <label class="form-label">{{ str_replace('_', ' ', ucfirst($key)) }}</label>
                    <textarea class="form-control" name="texts[{{ $key }}]" rows="3">{{ old("texts.$key", ($settings->texts[$key] ?? $value)) }}</textarea>
                    @error("texts.$key")<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
            @endforeach
        </div>
    </div>
    <div class="card-footer"><button class="btn btn-primary">Guardar alterações</button></div>
</form>
@endsection
