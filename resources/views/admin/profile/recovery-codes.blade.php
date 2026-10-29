@extends('cms-core::admin.layouts.app', ['title' => 'Códigos de recuperação'])

@section('content')
    <section class="bg-white border rounded p-4">
        <h1 class="h3">Códigos de recuperação</h1>
        <p class="alert alert-warning">Guarde estes códigos num local seguro. São apresentados apenas uma vez e cada código só pode ser utilizado uma vez.</p>
        <p>Permitem iniciar sessão se perder o acesso à aplicação de autenticação. Os códigos anteriores deixam de funcionar.</p>
        <ul>
            @foreach ($codes as $code)
                <li><code>{{ $code }}</code></li>
            @endforeach
        </ul>
        <a class="btn btn-primary" href="{{ route('admin.profile.edit') }}">Voltar aos meus dados</a>
    </section>
@endsection
