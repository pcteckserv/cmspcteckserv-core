<h2 class="h5 mb-2">1. Onde guardar os backups?</h2>
@php($selectedProtocol = old('protocol', $destination->protocol === 'ftp' && $destination->ssl ? 'ftps' : $destination->protocol))
<p class="text-secondary small">Escolha o destino e guarde a configuração antes de testar ou ligar a conta.</p>
<div class="row g-3">
    <div class="col-12">
        <label class="form-label" for="protocol">Destino</label>
        <select id="protocol" name="protocol" class="form-select">
            @foreach (['ftp' => 'Servidor FTP', 'google' => 'Google Drive'] as $value => $label)
                <option value="{{ $value }}" @selected($selectedProtocol === $value)>{{ $label }}</option>
            @endforeach
            <optgroup label="Outros destinos / configurações existentes">
                @foreach (['local' => 'Neste servidor', 'ftps' => 'Servidor FTPS (TLS)', 'sftp' => 'SFTP — disco configurado', 's3' => 'Amazon S3 — disco configurado', 'r2' => 'Cloudflare R2 — disco configurado'] as $value => $label)
                    <option value="{{ $value }}" @selected($selectedProtocol === $value)>{{ $label }}</option>
                @endforeach
            </optgroup>
        </select>
    </div>
    <div class="col-12">
        <label class="form-label" for="destination_name">Nome do destino</label>
        <input id="destination_name" name="name" class="form-control" value="{{ old('name', $destination->name) }}" required maxlength="255">
    </div>
</div>
<fieldset class="mt-3" data-backup-protocols="ftp ftps sftp s3 r2">
    <legend class="h6">Ligação ao servidor</legend>
    <div class="row g-3">
        <div class="col-sm-8"><label class="form-label" for="host">Servidor / host</label><input id="host" name="host" class="form-control" value="{{ old('host', $destination->host) }}" placeholder="ftp.exemplo.pt"></div>
        <div class="col-sm-4"><label class="form-label" for="port">Porta</label><input id="port" name="port" type="number" min="1" max="65535" class="form-control" value="{{ old('port', $destination->port) }}" placeholder="21"></div>
        <div class="col-sm-6"><label class="form-label" for="username">Utilizador</label><input id="username" name="username" class="form-control" value="{{ old('username', $destination->username) }}" autocomplete="off"></div>
        <div class="col-sm-6"><label class="form-label" for="password">Palavra-passe</label><input id="password" name="password" type="password" class="form-control" autocomplete="new-password" placeholder="{{ $destination->password ? 'Já guardada' : '' }}"><div class="form-text">Em branco mantém a atual.</div></div>
    </div>
    <div class="form-check mt-3" data-backup-protocols="ftp ftps"><input id="passive" name="passive" value="1" type="checkbox" class="form-check-input" @checked(old('passive', $destination->passive))><label for="passive" class="form-check-label">Modo passivo</label></div>
    <div class="form-text">No FTP/FTPS, os dados acima são usados diretamente. FTPS encripta o transporte; a extensão FTP do PHP não valida o certificado do servidor. SFTP, S3 e R2 usam um disco Laravel já configurado no alojamento.</div>
</fieldset>
<fieldset class="mt-3" data-backup-protocols="google">
    <legend class="h6">Ligar o Google Drive</legend>
    <div class="mb-3"><label class="form-label" for="google_client_id">ID do cliente Google</label><input id="google_client_id" name="google_client_id" class="form-control" value="{{ old('google_client_id', $destination->google_client_id) }}" maxlength="512" autocomplete="off"></div>
    <div class="mb-3"><label class="form-label" for="google_client_secret">Segredo do cliente Google</label><input id="google_client_secret" name="google_client_secret" type="password" class="form-control" autocomplete="new-password" placeholder="{{ $destination->google_client_secret ? 'Já guardado — deixe em branco para manter' : '' }}"></div>
    <details class="mb-3"><summary>Como obter estes dados?</summary>
        <ol class="small mt-2 ps-3">
            <li>No <a href="https://console.cloud.google.com/" target="_blank" rel="noopener noreferrer">Google Cloud</a>, crie ou escolha um projeto e ative a Google Drive API.</li>
            <li>Configure o ecrã de consentimento em Google Auth Platform e crie um cliente OAuth do tipo «Aplicação Web».</li>
            <li>Adicione este URI de redirecionamento autorizado, exatamente como aparece: <code class="d-block text-break mt-1">{{ route('admin.backups.google.callback', $destination) }}</code></li>
            <li>Copie o ID e o segredo para estes campos, guarde e carregue em «Ligar com Google». Escolha a conta e autorize o acesso aos ficheiros criados pelo CMS.</li>
        </ol>
        <p class="small text-secondary">Em modo de teste, adicione a conta aos utilizadores de teste. A Google pode fazer expirar a autorização após sete dias; para backups contínuos, prepare a aplicação para produção no Google Cloud.</p>
    </details>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-outline-primary" type="submit" form="backup-google-connect" @disabled($destination->protocol !== 'google')>{{ $destination->google_tokens ? 'Voltar a ligar com Google' : 'Ligar com Google' }}</button>
        @if ($destination->google_tokens)<button class="btn btn-outline-secondary" type="submit" form="backup-google-disconnect">Desligar conta</button>@endif
    </div>
    <p class="small text-secondary mt-2 mb-0">{{ $destination->google_tokens ? 'Conta autorizada. Os tokens são guardados encriptados.' : 'Ainda sem autorização Google.' }} O CMS cria uma pasta própria e só elimina os seus ficheiros de backup.</p>
</fieldset>
<div class="row g-3 mt-1">
    <div class="col-12"><label class="form-label" for="remote_path">Pasta de backups</label><input id="remote_path" name="remote_path" class="form-control" value="{{ old('remote_path', $destination->remote_path) }}" required maxlength="255"><div class="form-text">No Google, o nome é usado ao criar a pasta na primeira ligação. Os backups existentes conservam o destino original.</div></div>
    <div class="col-sm-6"><label class="form-label" for="timeout">Tempo limite da ligação (segundos)</label><input id="timeout" name="timeout" type="number" min="5" max="300" class="form-control" value="{{ old('timeout', $destination->timeout) }}" required></div>
    <div class="col-sm-6" data-backup-protocols="local sftp s3 r2"><label class="form-label" for="disk">Disco Laravel configurado</label><input id="disk" name="disk" class="form-control" value="{{ old('disk', $destination->disk) }}" required></div>
</div>
<div class="d-flex flex-wrap gap-2 mt-4">
    <button class="btn btn-primary" type="submit">Guardar destino</button>
    <button class="btn btn-outline-primary" type="submit" form="backup-destination-test-form">Testar ligação guardada</button>
</div>
<p class="small text-secondary mt-2 mb-0" role="status">Estado: {{ ['connected'=>'Ligado','not_tested'=>'Por testar','error'=>'Falha de ligação'][$destination->connection_status] ?? $destination->connection_status }} @if($destination->last_tested_at) · {{ $destination->last_tested_at->format('d/m/Y H:i') }} @endif</p>
