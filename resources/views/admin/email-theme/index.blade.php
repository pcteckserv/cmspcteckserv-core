@extends('cms-core::admin.layouts.app', ['title' => 'Tema de email'])
@section('content')
<div class="mb-4">
    <h1 class="h3">Tema de email</h1>
    <p class="text-secondary">Tema global partilhado pelos emails da Loja, de Clientes e dos módulos que utilizam o tema do Core.</p>
</div>
@if(session('cms_email_theme_success'))<div class="alert alert-success" role="status">{{ session('cms_email_theme_success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<section class="bg-white border rounded-2 p-4 mb-4" aria-labelledby="email-theme-preview-title">
    <h2 id="email-theme-preview-title" class="h5">Pré-visualização renderizada</h2>
    <p>Este exemplo utiliza o código atual do tema e dados simulados. Não envia emails.</p>
    <iframe class="w-100 border rounded" height="640" src="{{ route('admin.email-theme.preview') }}" title="Pré-visualização do tema global de email" sandbox loading="lazy" referrerpolicy="no-referrer"></iframe>
    <a class="btn btn-outline-secondary mt-3" href="{{ route('admin.email-theme.preview') }}" target="_blank" rel="noopener">Abrir pré-visualização</a>
</section>
@can('core.email-theme.test')
<section class="bg-white border rounded-2 p-4 mb-4" aria-labelledby="email-theme-test-title">
    <h2 id="email-theme-test-title" class="h5">Enviar email de teste</h2>
    <p>Envia este exemplo com o tema aplicado e a configuração de correio do site. Máximo de cinco pedidos por minuto.</p>
    <form method="post" action="{{ route('admin.email-theme.test') }}">
        @csrf
        <label class="form-label" for="email-theme-recipient">Destinatário do teste</label>
        <input id="email-theme-recipient" class="form-control @error('recipient') is-invalid @enderror" type="email" name="recipient" maxlength="254" required value="{{ old('recipient', auth()->user()->email) }}" aria-describedby="email-theme-recipient-help" @error('recipient') aria-invalid="true" @enderror>
        <p class="form-text" id="email-theme-recipient-help">A aceitação pelo transporte não garante a entrega na caixa de entrada. Verifique também o correio não solicitado.</p>
        <button class="btn btn-dark" type="submit">Enviar email de teste</button>
    </form>
</section>
@endcan
<section class="bg-white border rounded-2 p-4" aria-labelledby="email-theme-docs-title">
    <h2 id="email-theme-docs-title" class="h5">Como personalizar os emails por código</h2>
    <p>O tema base tem cabeçalho preto, conteúdo branco e rodapé. A aparência é definida no código do site; não existem seletores de cores nem um editor de código neste painel.</p>
    <ol>
        <li>Publique os ficheiros do tema na aplicação com <code>php artisan vendor:publish --tag=cms-core-email-theme</code>. Não utilize <code>--force</code> sobre ficheiros já personalizados.</li>
        <li>Personalize <code>resources/views/vendor/cms-core/emails/layout.blade.php</code> ou apenas <code>header.blade.php</code> e <code>footer.blade.php</code> no mesmo diretório. Nunca edite <code>vendor/</code>.</li>
        <li>O layout recebe <code>$email</code>, com <code>subject</code>, <code>heading</code>, <code>body</code> e, opcionalmente, <code>action_url</code> e <code>action_label</code>. Preserve a secção Blade <code>email-content</code>, utilizada pelos plugins para fornecer o conteúdo.</li>
        <li>Utilize tabelas e estilos compatíveis com email. Preserve o escaping Blade <code>@{{ $email['heading'] }}</code>; os textos configurados nunca devem ser interpretados como HTML ou Blade.</li>
        <li>Atualize esta página para ver o resultado e envie um email de teste para confirmar a apresentação no seu programa de correio.</li>
    </ol>
    <p>Para trabalhar com uma IA, indique-lhe estes ficheiros e o contrato <code>$email</code>. Peça-lhe para alterar apenas a apresentação no site, preservar <code>email-content</code> e o escaping, e não modificar o Core nem os plugins.</p>
    <p class="mb-0">Os assuntos, títulos, textos, variáveis de personalização e regras de envio continuam nas configurações do plugin responsável. Uma alteração ao tema global aplica-se aos módulos que utilizam este layout.</p>
</section>
@endsection
