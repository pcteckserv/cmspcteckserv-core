@extends('cms-core::admin.layouts.app',['title'=>['accounts'=>'Contas e registo','policies'=>'Políticas'][$section]])
@section('content')
<h1 class="h3 mb-4">{{ ['accounts'=>'Opções gerais','policies'=>'Opções gerais'][$section] }}</h1>
@if(in_array($section,['accounts','policies'])) @include('cms-core::admin.site-options.tabs') @endif
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
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

@endif
@can('core.site-options.update')<button class="btn btn-primary align-self-start" type="submit">Guardar alterações</button>@endcan
</form>
@endsection
