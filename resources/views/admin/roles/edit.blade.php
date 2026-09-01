@extends('cms-core::admin.layouts.app', ['title' => 'Editar role'])

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Editar role</h1>
        @can('core.roles.delete')
            <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" onsubmit="return confirm('Confirma a eliminação desta role?')">
                @csrf
                @method('DELETE')
                <button class="btn btn-outline-danger cms-media-icon-button" type="submit" title="Eliminar" aria-label="Eliminar" @disabled($role->is_protected)>@include('cms-core::components.icons.trash')</button>
            </form>
        @endcan
    </div>
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @include('cms-core::admin.roles.partials.form', ['action' => route('admin.roles.update', $role), 'method' => 'PUT'])
@endsection
