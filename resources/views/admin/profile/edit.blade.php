@extends('cms-core::admin.layouts.app', ['title' => 'Os meus dados'])

@section('content')
    <h1 class="h3 mb-4">Os meus dados</h1>

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">Corrija os campos assinalados.</div>
    @endif

    <form method="POST" enctype="multipart/form-data" action="{{ route('admin.profile.update') }}" class="bg-white border rounded p-4">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="name">Nome</label>
                <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" type="text" value="{{ old('name', $user->name) }}" maxlength="255" autocomplete="name" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="email">Email</label>
                <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email', $user->email) }}" maxlength="255" autocomplete="email" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label" for="current_password">Palavra-passe atual</label>
                <input class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" type="password" autocomplete="current-password" aria-describedby="current-password-help">
                @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text" id="current-password-help">Necessária para alterar o email ou a palavra-passe.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="password">Nova palavra-passe</label>
                <input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" autocomplete="new-password" aria-describedby="password-help">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text" id="password-help">Deixe em branco para manter a palavra-passe atual.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="password_confirmation">Confirmar nova palavra-passe</label>
                <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
            </div>
        </div>

        @include('cms-core::admin.partials.staff-profile')

        <div class="mt-4 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Guardar alterações</button>
            <a class="btn btn-outline-secondary" href="{{ route('admin.dashboard') }}">Cancelar</a>
        </div>
    </form>
    @include('cms-core::admin.profile.partials.two-factor')
@endsection
