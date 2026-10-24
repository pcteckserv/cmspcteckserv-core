@extends('cms-core::admin.layouts.app',['title'=>['accounts'=>'Contas e registo','policies'=>'Políticas','menus'=>'Menus','style'=>'Estilo'][$section]])
@section('content')
<h1 class="h3 mb-4">{{ ['accounts'=>'Opções gerais','policies'=>'Opções gerais','menus'=>'Menus','style'=>'Estilo'][$section] }}</h1>
@if(in_array($section,['accounts','policies'])) @include('cms-core::admin.site-options.tabs') @endif
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@if($section==='menus')
<div class="d-flex justify-content-between align-items-center mb-3"><span>{{ count($website->menus()) }} menus</span><a class="btn btn-primary" href="{{ route('admin.website.edit','menus') }}">Criar menu</a></div>
<div class="table-responsive mb-4"><table class="table"><thead><tr><th>Menu</th><th>Localização</th><th>Elementos</th><th class="text-end">Ações</th></tr></thead><tbody>@forelse($website->menus() as $menu)<tr><td>{{ $menu['name'] }}</td><td>{{ ['header'=>'Cabeçalho','footer'=>'Rodapé','custom'=>'Personalizada'][$menu['location']] }}</td><td>{{ count($menu['items']) }}</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.website.edit',['section'=>'menus','menu'=>$menu['slug']]) }}">Editar</a></td></tr>@empty<tr><td colspan="4">Ainda não existem menus.</td></tr>@endforelse</tbody></table></div>
@php($selected=$website->menu(request('menu','')))
@endif
<form method="post" action="{{ route('admin.website.update',$section) }}" class="card card-body">@csrf @method('PUT')
@if($section==='accounts')
<h2 class="h5">Contas e registo</h2><input type="hidden" name="registration" value="0"><label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="registration" value="1" @checked(old('registration',$website->registration()))> Permitir o registo público de clientes</label><p class="text-secondary">Aplica-se à conta de cliente partilhada pelos plugins. As contas existentes mantêm o acesso. Os utilizadores da administração são criados por um administrador.</p>
@if($website->commerce())<input type="hidden" name="require_account" value="0"><label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="require_account" value="1" @checked(old('require_account',$website->requireAccount()))> Exigir conta de cliente para finalizar compras na loja</label>@endif
@elseif($section==='policies')
<h2 class="h5">Textos e páginas legais</h2><p>Edite o texto de cada política. Os parágrafos e as quebras de linha são preservados. Os endereços e códigos abaixo apresentam sempre o texto guardado.</p>
@foreach(\Pcteckserv\CmsCore\Support\Website::POLICIES as $key=>$label)
<details class="border rounded p-3 mb-3" @if($errors->has('policies.'.$key)) open @endif><summary class="fw-semibold">{{ $label }} @if(in_array($key,['terms','refunds']) && $website->commerce())<span class="badge bg-warning text-dark">Necessário para a loja</span>@endif @if(trim($website->policy($key))==='')<span class="small text-secondary">Por preencher</span>@endif</summary>
<label class="form-label d-block mt-3">Texto<textarea class="form-control" name="policies[{{ $key }}]" rows="14" maxlength="100000">{{ old('policies.'.$key,$website->policy($key)) }}</textarea></label>
@php($url=route('website.policy',\Pcteckserv\CmsCore\Support\Website::SLUGS[$key]))
<label class="form-label d-block">Endereço público<input class="form-control" readonly value="{{ $url }}"></label><label class="form-label d-block">Código HTML para colocar numa página<textarea class="form-control font-monospace" rows="3" readonly>{{ '<iframe src="'.$url.'" title="'.$label.'" style="width:100%;height:700px;border:0" loading="lazy"></iframe>' }}</textarea></label><small>A página pública fica disponível depois de guardar um texto.</small></details>
@endforeach
@elseif($section==='style')
<h2 class="h5">CSS personalizado do site</h2><p>Estes estilos são carregados depois dos estilos do tema nas páginas públicas, incluindo as páginas dos plugins. Use os seletores do seu tema para ajustar cores, espaçamentos e apresentação.</p><label class="form-label d-block">Folha de estilos CSS<textarea name="css" rows="24" maxlength="100000" class="form-control font-monospace" spellcheck="false" placeholder=".site-title { color: #ff6b35; }">{{ old('css',$website->get('css','')) }}</textarea></label><p class="small">Endereço da folha: <a href="{{ route('website.css') }}" target="_blank" rel="noopener">{{ route('website.css') }}</a>. Para repor o estilo original, limpe este campo e guarde.</p>
@else
<h2 class="h5">{{ $selected?'Editar menu':'Criar menu' }}</h2><input type="hidden" name="original_slug" value="{{ old('original_slug',$selected['slug']??'') }}"><div class="row g-3 mb-3"><label class="col-md-4">Nome<input class="form-control" name="name" required maxlength="100" value="{{ old('name',$selected['name']??'') }}"></label><label class="col-md-4">Identificador<input class="form-control" name="slug" required pattern="[a-z0-9]+(-[a-z0-9]+)*" maxlength="100" value="{{ old('slug',$selected['slug']??'') }}"></label><label class="col-md-4">Localização<select class="form-select" name="location">@foreach(['header'=>'Cabeçalho','footer'=>'Rodapé','custom'=>'Personalizada'] as $key=>$label)<option value="{{ $key }}" @selected(old('location',$selected['location']??'custom')===$key)>{{ $label }}</option>@endforeach</select></label></div>
<p>Adicione ligações a páginas, políticas ou endereços externos. Escolha um elemento superior para criar submenus e indique a ordem de apresentação.</p>
<label class="form-label">Adicionar uma política<select class="form-select" data-policy-choice><option value="">Escolher política…</option>@foreach(\Pcteckserv\CmsCore\Support\Website::POLICIES as $key=>$label)<option value="{{ route('website.policy',\Pcteckserv\CmsCore\Support\Website::SLUGS[$key]) }}">{{ $label }}</option>@endforeach</select></label>
<div data-menu-items>@foreach(old('items',$selected['items']??[]) as $index=>$item) @include('cms-core::admin.website.menu-item',['index'=>$index,'item'=>$item]) @endforeach</div>
<button type="button" class="btn btn-outline-secondary align-self-start mb-3" data-menu-add>Adicionar ligação</button><template data-menu-template>@include('cms-core::admin.website.menu-item',['index'=>'__INDEX__','item'=>[]])</template>
@endif
@can('core.site-options.update')<button class="btn btn-primary align-self-start" type="submit">Guardar alterações</button>@endcan
</form>
@if($section==='menus' && $selected)
<div class="card card-body mt-3"><h2 class="h5">Utilizar este menu</h2><a href="{{ route('website.menu',$selected['slug']) }}" target="_blank" rel="noopener">Ver menu</a><label class="form-label mt-3">Código HTML<textarea class="form-control font-monospace" readonly rows="3">{{ '<iframe src="'.route('website.menu',$selected['slug']).'" title="'.$selected['name'].'" style="width:100%;height:240px;border:0" loading="lazy"></iframe>' }}</textarea></label><p class="small">No tema Blade: <code>{{ '<'.'x-cms-menu slug="'.$selected['slug'].'" />' }}</code>. Pela localização: <code>{{ '<'.'x-cms-menu location="'.$selected['location'].'" />' }}</code>.</p><form method="post" action="{{ route('admin.website.menus.destroy',$selected['slug']) }}">@csrf @method('DELETE')<button class="btn btn-outline-danger" data-confirm-delete="Eliminar este menu?">Eliminar menu</button></form></div>
@endif
@if($section==='menus')<script src="{{ route('admin.website.admin-script') }}" defer></script>@endif
@endsection
