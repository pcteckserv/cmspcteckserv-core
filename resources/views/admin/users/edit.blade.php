@extends('cms-core::admin.layouts.app', ['title' => 'Editar utilizador'])

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Editar utilizador</h1>
        <div class="d-flex gap-2">
            @can('core.users.delete')
                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Confirma a desativação deste utilizador?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger" type="submit">Desativar</button>
                </form>
            @endcan
            @include('cms-core::admin.users.partials.delete')
        </div>
    </div>
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @include('cms-core::admin.users.partials.form', ['action' => route('admin.users.update', $user), 'method' => 'PUT'])
@endsection
