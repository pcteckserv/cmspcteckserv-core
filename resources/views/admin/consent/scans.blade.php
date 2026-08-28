@extends('cms-core::admin.layouts.app', ['title' => 'Scanner de consentimentos'])

@section('content')
<h1 class="h3 mb-3">Scanner</h1>
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<form method="POST" action="{{ route('admin.consent.scans.store') }}" class="card mb-4">
    @csrf
    <div class="card-body">
        <label class="form-label">URLs adicionais, uma por linha</label>
        <textarea class="form-control" name="urls" rows="4"></textarea>
    </div>
    <div class="card-footer"><button class="btn btn-primary">Executar nova análise</button></div>
</form>
<div class="card"><table class="table mb-0"><thead><tr><th>Data</th><th>Estado</th><th>Páginas</th><th>Tecnologias</th><th>Alterações</th></tr></thead><tbody>
@foreach($scans as $scan)
<tr><td>{{ $scan->created_at->format('d/m/Y H:i') }}</td><td>{{ $scan->status }}</td><td>{{ $scan->pages_scanned }}</td><td>{{ $scan->technologies_found }}</td><td>{{ $scan->changes_found }}</td></tr>
@endforeach
</tbody></table></div>
{{ $scans->links() }}
@endsection
