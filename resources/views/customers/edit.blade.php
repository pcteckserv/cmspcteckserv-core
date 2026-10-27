@extends('cms-core::admin.layouts.app', ['title'=>'Ficha do cliente'])
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h1>{{ $customer->exists?$customer->name:'Criar cliente' }}</h1><a href="{{ route('admin.customers.index') }}">Voltar aos clientes</a></div>
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<section class="card p-3"><h2>Dados do cliente</h2><form autocomplete="off" method="post" action="{{ $customer->exists?route('admin.customers.update',$customer->id):route('admin.customers.store') }}">@csrf @if($customer->exists)<input type="hidden" name="account_form_controlled" value="1"><label class="d-block"><input type="checkbox" name="change_email" value="1" data-enable-email-change> Alterar email</label> @endif @if($customer->exists) @method('PUT') @endif
<div class="row g-3">@foreach(['name'=>'Nome','email'=>'Email','company'=>'Empresa','phone'=>'Telefone','vat'=>'Contribuinte'] as $field=>$label)<label class="col-md-6">{{ $label }}<input class="form-control" name="{{ $field }}" @if($field==='email' && $customer->exists) readonly data-edit-email autocomplete="off" @endif type="{{ $field==='email'?'email':'text' }}" value="{{ old($field,$customer->$field) }}" @if(in_array($field,['name','email'])) required @endif @cannot('core.customers.manage') disabled @endcannot></label>@endforeach
<label class="col-md-6">Estado<select name="active" class="form-select" @cannot('core.customers.manage') disabled @endcannot><option value="1" @selected(old('active',$customer->active??true))>Ativo</option><option value="0" @selected(!old('active',$customer->active??true))>Inativo</option></select></label><label class="col-md-6">Grupos<select class="form-select" name="groups[]" multiple @cannot('core.customers.manage') disabled @endcannot>@foreach($groups as $group)<option value="{{ $group->id }}" @selected(in_array($group->id,old('groups',$customer->exists?$customer->groups->modelKeys():[])))>{{ $group->name }}</option>@endforeach</select></label></div>
@can('core.customers.manage')
<fieldset class="border rounded p-3 mt-3"><legend class="h5">Acesso do cliente</legend>
<label class="d-block"><input type="checkbox" name="change_password" value="1" data-enable-password-change @checked(old('change_password'))> {{ $customer->exists?'Alterar palavra-passe':'Definir palavra-passe agora' }}</label>
<p class="small text-secondary">{{ $customer->exists?'Ative apenas se pretender definir uma nova palavra-passe. Sem esta opção, a atual mantém-se.':'Opcional. Se não definir uma palavra-passe, o cliente pode usar a recuperação de acesso para criar a sua.' }}</p>
<div class="row g-3"><label class="col-md-6">{{ $customer->exists?'Nova palavra-passe':'Palavra-passe' }}<input class="form-control" type="password" name="password" minlength="12" maxlength="200" autocomplete="new-password" data-lpignore="true" data-1p-ignore="true" data-new-password disabled required></label>
<label class="col-md-6">Confirmar palavra-passe<input class="form-control" type="password" name="password_confirmation" minlength="12" maxlength="200" autocomplete="new-password" data-lpignore="true" data-1p-ignore="true" data-new-password disabled required></label></div>
<small>Mínimo de 12 caracteres. Os campos ficam sempre vazios.</small>
</fieldset>
<button class="btn btn-primary mt-3">Guardar cliente</button>@endcan</form></section>
@if($customer->exists)
<section class="card p-3 my-3"><h2>Moradas guardadas</h2><p>As alterações aplicam-se a utilizações futuras; os dados históricos das encomendas são preservados.</p>
@foreach($customer->addresses as $address)
<details class="border rounded p-3 mb-2"><summary>{{ $address->label }} — {{ $address->details['address'] ?? '' }}</summary>@can('core.customers.manage')@include('cms-core::customers.address-form', ['address'=>$address])<form autocomplete="off" method="post" action="{{ route('admin.customers.addresses.destroy',[$customer->id,$address->id]) }}">@csrf @method('DELETE')<button class="btn btn-outline-danger">Remover morada</button></form>@else<p>{{ implode(', ',array_filter($address->details)) }}</p>@endcan</details>
@endforeach
@can('core.customers.manage')<details class="border rounded p-3"><summary>Adicionar morada</summary>@include('cms-core::customers.address-form', ['address'=>null])</details>@endcan</section>
@include('cms-core::customers.history')
@can('core.customers.manage')<form autocomplete="off" method="post" action="{{ route('admin.customers.destroy',$customer->id) }}" onsubmit="return confirm('Eliminar este cliente se não existir histórico, ou inativar se já tiver movimentos?')">@csrf @method('DELETE')<button class="btn btn-outline-danger">{{ $customer->hasHistory()?'Inativar cliente':'Eliminar cliente' }}</button></form>@endcan
@endif
<script src="{{ route('cms-core.account-form-script',['v'=>'2.19.1']) }}" defer></script>
@endsection
