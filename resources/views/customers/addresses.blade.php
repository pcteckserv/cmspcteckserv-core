@extends('cms-core::customers.layout')
@section('content')<h1>As minhas moradas</h1><p>As alterações aplicam-se a utilizações futuras. Os dados de encomendas anteriores são preservados.</p>
@foreach($addresses->concat([null]) as $address)<section><h2>{{ $address?->label ?? 'Adicionar morada' }}</h2>
<form method="post" action="{{ $address?route('customers.addresses.update',$address->id):route('customers.addresses.store') }}">@csrf @if($address) @method('PUT') @endif<div class="grid">
@foreach(['label'=>'Identificação','name'=>'Destinatário','company'=>'Empresa','address'=>'Rua e número','city'=>'Localidade','municipality'=>'Concelho','postal_code'=>'Código postal','vat'=>'Contribuinte'] as $field=>$label)<label>{{ $label }}<input name="{{ $field }}" value="{{ $field==='label'?($address?->label??''):($address?->details[$field]??'') }}" @if(in_array($field,['label','name','address','city','postal_code'])) required @endif></label>@endforeach
<label>País<select name="country" required>@foreach(\Pcteckserv\CmsCore\Customers\AddressFields::countries() as $code=>$name)<option value="{{ $code }}" @selected(($address?->details['country']??'PT')===$code)>{{ $name }}</option>@endforeach</select></label></div><button>Guardar morada</button></form>
@if($address)<form method="post" action="{{ route('customers.addresses.destroy',$address->id) }}">@csrf @method('DELETE')<button>Remover morada</button></form>@endif</section>@endforeach
@endsection
