@extends('cms-core::admin.layouts.app', ['title' => 'Auditoria SEO'])

@section('content')
    <h1 class="h3 mb-4">Auditoria SEO</h1>
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>URL</th><th>Estado HTTP</th><th>Score</th><th>Problemas</th><th>Analisado em</th></tr></thead>
                <tbody>
                    @forelse ($audits as $audit)
                        <tr>
                            <td class="text-break">{{ $audit->url }}</td>
                            <td>{{ $audit->status_code }}</td>
                            <td><span class="badge text-bg-{{ $audit->score >= 75 ? 'success' : ($audit->score >= 50 ? 'warning' : 'danger') }}">{{ $audit->score }}/100</span></td>
                            <td>{{ count($audit->results ?? []) }}</td>
                            <td>{{ $audit->scanned_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-secondary py-4">Ainda não existem auditorias registadas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $audits->links() }}</div>
    </div>
@endsection
