<nav class="nav nav-tabs mb-4" aria-label="Opções gerais">
@foreach(['general'=>'Geral','accounts'=>'Contas e registo','policies'=>'Políticas'] as $key=>$label)
<a class="nav-link {{ ($section??'general')===$key?'active':'' }}" href="{{ $key==='general'?route('admin.site-options.edit'):route('admin.website.edit',$key) }}">{{ $label }}</a>
@endforeach
</nav>
