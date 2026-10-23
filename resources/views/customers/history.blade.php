@foreach($sections as $section)
<section class="card p-3 my-3" id="customer-history-{{ $section['key'] ?? 'module' }}"><h2>{{ $section['label'] }}</h2><div class="scroll table-responsive"><table class="table"><thead><tr><th>Referência / serviço</th><th>Data</th><th>Estado</th><th>Valor</th><th></th></tr></thead><tbody>
@forelse($section['records'] as $record)<tr><td>{{ $record['title'] }}</td><td>{{ $record['date'] }}</td><td>{{ $record['status'] }}</td><td>{{ $record['amount'] }}</td><td>@if($record['url'] ?? null)<a href="{{ $record['url'] }}">Ver detalhes</a>@endif</td></tr>@empty<tr><td colspan="5">Ainda não existem registos.</td></tr>@endforelse
</tbody></table></div>@if(method_exists($section['records'], 'links')){{ $section['records']->links() }}@endif</section>
@endforeach
