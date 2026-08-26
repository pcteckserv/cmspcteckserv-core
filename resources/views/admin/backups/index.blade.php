@extends('cms-core::admin.layouts.app', ['title' => 'Backups'])

@php
    $events = array_replace(config('cms-backups.notifications.events', []), $plan->notification_events ?: []);
@endphp

@section('content')
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Backups</h1>
            <p class="text-secondary mb-0">Gestão de backups automáticos, destino remoto, histórico e alertas.</p>
        </div>
        <div class="text-lg-end small text-secondary">
            <div>Último backup: {{ optional($lastRun?->created_at)->format('d/m/Y H:i') ?? 'Sem execuções' }}</div>
            <div>Scheduler: {{ $heartbeat && $heartbeat->ran_at->gt(now()->subMinutes(5)) ? 'Ativo' : 'Não foi detetada execução recente' }}</div>
        </div>
    </div>

    @if (session('cms_backups_success'))
        <div class="alert alert-success">{{ session('cms_backups_success') }}</div>
    @endif

    @if (session('cms_backups_error'))
        <div class="alert alert-danger">{{ session('cms_backups_error') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-xl-6">
            <form class="bg-white border rounded-2 p-4 h-100" method="POST" action="{{ route('admin.backups.destinations.update', $destination) }}">
                @csrf
                @method('PUT')

                <h2 class="h5 mb-3">Destino</h2>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="destination_name">Nome</label>
                        <input id="destination_name" name="name" class="form-control" value="{{ old('name', $destination->name) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="protocol">Protocolo</label>
                        <select id="protocol" name="protocol" class="form-select">
                            @foreach (['local' => 'Local', 'ftp' => 'FTP', 'ftps' => 'FTPS', 'sftp' => 'SFTP', 's3' => 'Amazon S3', 'r2' => 'Cloudflare R2'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('protocol', $destination->protocol) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="disk">Disco Laravel</label>
                        <input id="disk" name="disk" class="form-control" value="{{ old('disk', $destination->disk) }}" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="host">Host</label>
                        <input id="host" name="host" class="form-control" value="{{ old('host', $destination->host) }}" placeholder="ftp.exemplo.pt">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="port">Porta</label>
                        <input id="port" name="port" type="number" min="1" max="65535" class="form-control" value="{{ old('port', $destination->port) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="username">Utilizador</label>
                        <input id="username" name="username" class="form-control" value="{{ old('username', $destination->username) }}" autocomplete="username">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password">Palavra-passe</label>
                        <input id="password" name="password" type="password" class="form-control" value="" placeholder="{{ $destination->password ? '••••••••' : '' }}" autocomplete="new-password">
                        <div class="form-text">Deixe em branco para manter a palavra-passe atual.</div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="remote_path">Diretoria</label>
                        <input id="remote_path" name="remote_path" class="form-control" value="{{ old('remote_path', $destination->remote_path) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="timeout">Timeout</label>
                        <input id="timeout" name="timeout" type="number" min="5" max="300" class="form-control" value="{{ old('timeout', $destination->timeout) }}" required>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-3 mt-3">
                    <div class="form-check"><input class="form-check-input" name="passive" value="1" type="checkbox" @checked(old('passive', $destination->passive)) id="passive"><label class="form-check-label" for="passive">Modo passivo</label></div>
                    <div class="form-check"><input class="form-check-input" name="ssl" value="1" type="checkbox" @checked(old('ssl', $destination->ssl)) id="ssl"><label class="form-check-label" for="ssl">SSL/TLS</label></div>
                    <div class="form-check"><input class="form-check-input" name="verify_ssl" value="1" type="checkbox" @checked(old('verify_ssl', $destination->verify_ssl)) id="verify_ssl"><label class="form-check-label" for="verify_ssl">Verificar certificado SSL</label></div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 mt-4">
                    <button class="btn btn-primary" type="submit">Guardar destino</button>
                </div>
            </form>

            <form method="POST" class="mt-2" action="{{ route('admin.backups.destinations.test', $destination) }}">
                @csrf
                <button class="btn btn-outline-primary" type="submit">Testar ligação</button>
                <span class="ms-2 small text-secondary">Estado: {{ $destination->connection_status }} @if($destination->last_tested_at) · {{ $destination->last_tested_at->format('d/m/Y H:i') }} @endif</span>
            </form>
        </div>

        <div class="col-xl-6">
            <form class="bg-white border rounded-2 p-4" method="POST" action="{{ route('admin.backups.plans.update', $plan) }}">
                @csrf
                @method('PUT')

                <h2 class="h5 mb-3">Plano de Backup</h2>

                <div class="form-check form-switch mb-3">
                    <input id="enabled" name="enabled" type="checkbox" value="1" class="form-check-input" @checked(old('enabled', $plan->enabled))>
                    <label class="form-check-label" for="enabled">Backup automático agendado</label>
                </div>

                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label" for="name">Plano</label><input id="name" name="name" class="form-control" value="{{ old('name', $plan->name) }}" required></div>
                    <div class="col-md-6"><label class="form-label" for="type">Tipo</label><select id="type" name="type" class="form-select">@foreach(['full'=>'Completo','database'=>'Apenas base de dados','files'=>'Apenas ficheiros','media'=>'Apenas media/uploads'] as $value=>$label)<option value="{{ $value }}" @selected(old('type', $plan->type)===$value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label" for="frequency">Frequência</label><select id="frequency" name="frequency" class="form-select">@foreach(['daily'=>'Diário','weekly'=>'Semanal','weekdays'=>'Vários dias da semana','monthly'=>'Mensal','hourly'=>'De hora a hora','every_2_hours'=>'A cada 2 horas','every_3_hours'=>'A cada 3 horas','every_4_hours'=>'A cada 4 horas','every_6_hours'=>'A cada 6 horas','every_8_hours'=>'A cada 8 horas','every_12_hours'=>'A cada 12 horas'] as $value=>$label)<option value="{{ $value }}" @selected(old('frequency', $plan->frequency)===$value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label" for="run_at">Hora</label><input id="run_at" name="run_at" type="time" class="form-control" value="{{ old('run_at', substr((string) $plan->run_at, 0, 5)) }}" required></div>
                    <div class="col-md-3"><label class="form-label" for="month_day">Dia do mês</label><input id="month_day" name="month_day" type="number" min="1" max="31" class="form-control" value="{{ old('month_day', $plan->month_day) }}"></div>
                    <div class="col-md-6"><label class="form-label" for="storage_mode">Guardar</label><select id="storage_mode" name="storage_mode" class="form-select">@foreach(['local_and_remote'=>'Local + remoto','local'=>'Apenas local','remote'=>'Apenas remoto'] as $value=>$label)<option value="{{ $value }}" @selected(old('storage_mode', $plan->storage_mode)===$value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label" for="retention_days">Retenção em dias</label><input id="retention_days" name="retention_days" type="number" min="1" max="3650" class="form-control" value="{{ old('retention_days', $plan->retention_days) }}"></div>
                    <div class="col-md-3"><label class="form-label" for="retention_count">Máximo</label><input id="retention_count" name="retention_count" type="number" min="1" class="form-control" value="{{ old('retention_count', $plan->retention_count) }}"></div>
                    <input type="hidden" name="compression" value="zip">
                </div>

                <details class="mt-4">
                    <summary class="fw-semibold">Opções avançadas</summary>
                    <div class="row g-3 mt-2">
                        <div class="col-md-6"><label class="form-label" for="included_paths">Diretórios incluídos</label><textarea id="included_paths" name="included_paths" rows="5" class="form-control">{{ old('included_paths', implode("\n", $plan->included_paths ?: [])) }}</textarea></div>
                        <div class="col-md-6"><label class="form-label" for="excluded_paths">Diretórios excluídos</label><textarea id="excluded_paths" name="excluded_paths" rows="5" class="form-control">{{ old('excluded_paths', implode("\n", $plan->excluded_paths ?: [])) }}</textarea></div>
                    </div>
                    <div class="alert alert-warning mt-3 mb-0">O ficheiro .env está excluído por defeito. Só deve ser incluído após avaliação explícita dos riscos de segurança.</div>
                </details>

                <h3 class="h6 mt-4">Notificações e Alertas</h3>
                <label class="form-label" for="notification_emails">Email(s) para alertas</label>
                <textarea id="notification_emails" name="notification_emails" rows="3" class="form-control mb-3">{{ old('notification_emails', implode("\n", $plan->notification_emails ?: [])) }}</textarea>

                <div class="row g-2">
                    @foreach(['backup_failed'=>'Backup falhar','remote_upload_failed'=>'Upload remoto falhar','backup_missing'=>'Backup não executado','backup_corrupted'=>'Backup corrompido','backup_succeeded'=>'Backup concluído com sucesso','retention_deleted'=>'Retenção eliminar backups antigos','recovery'=>'Problema resolvido'] as $event=>$label)
                        @php $input = $event === 'recovery' ? 'notify_recovery' : 'notify_'.$event; @endphp
                        <div class="col-md-6"><div class="form-check"><input class="form-check-input" id="{{ $input }}" name="{{ $input }}" value="1" type="checkbox" @checked(old($input, $event === 'recovery' ? $plan->notify_recovery : ($events[$event] ?? false)))><label class="form-check-label" for="{{ $input }}">{{ $label }}</label></div></div>
                    @endforeach
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-6"><label class="form-label" for="alert_timing">Enviar alerta</label><select id="alert_timing" name="alert_timing" class="form-select"><option value="after_retries" @selected($plan->alert_timing==='after_retries')>Após esgotar tentativas</option><option value="first_failure" @selected($plan->alert_timing==='first_failure')>Na primeira falha</option></select></div>
                    <div class="col-md-6"><label class="form-label" for="repeat_alert_after_minutes">Repetir alerta após</label><input id="repeat_alert_after_minutes" name="repeat_alert_after_minutes" type="number" min="15" class="form-control" value="{{ old('repeat_alert_after_minutes', $plan->repeat_alert_after_minutes) }}"></div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button class="btn btn-primary" type="submit">Guardar configuração</button>
                </div>
            </form>

            <form class="mt-2" method="POST" action="{{ route('admin.backups.plans.test-email', $plan) }}">
                @csrf
                <button class="btn btn-outline-primary" type="submit">Enviar email de teste</button>
            </form>
        </div>
    </div>

    <div class="bg-white border rounded-2 p-4 mt-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
            <div>
                <h2 class="h5 mb-1">Backup Instantâneo</h2>
                <div class="text-secondary small">Próximo automático: {{ optional($plan->next_run_at)->format('d/m/Y H:i') ?? 'Não calculado' }} · Timezone: {{ $plan->timezone }}</div>
            </div>
            <form class="d-flex flex-wrap gap-2" method="POST" action="{{ route('admin.backups.plans.run', $plan) }}">
                @csrf
                <select name="type" class="form-select"><option value="full">Completo</option><option value="database">Apenas base de dados</option><option value="files">Apenas ficheiros</option><option value="media">Apenas media/uploads</option></select>
                <select name="storage_mode" class="form-select"><option value="local_and_remote">Local + remoto</option><option value="local">Apenas local</option><option value="remote">Apenas remoto</option></select>
                <button class="btn btn-primary" type="submit">Criar Backup Agora</button>
            </form>
        </div>
    </div>

    <div class="bg-white border rounded-2 p-4 mt-4">
        <h2 class="h5 mb-3">Histórico de Execuções</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Data</th><th>Plano</th><th>Tipo</th><th>Estado</th><th>Tamanho</th><th>Duração</th><th>Checksum</th><th>Ações</th></tr></thead>
                <tbody>
                    @forelse($runs as $run)
                        <tr>
                            <td>{{ $run->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $run->plan?->name ?? 'Manual' }}</td>
                            <td>{{ $run->type }}</td>
                            <td><span class="badge text-bg-{{ $run->status === 'success' ? 'success' : ($run->status === 'failed' ? 'danger' : 'warning') }}">{{ $run->status }}</span></td>
                            <td>{{ $run->size_bytes ? number_format($run->size_bytes / 1024 / 1024, 2, ',', ' ') . ' MB' : '-' }}</td>
                            <td>{{ $run->duration_seconds ? $run->duration_seconds . ' s' : '-' }}</td>
                            <td class="small">{{ $run->checksum_sha256 ? substr($run->checksum_sha256, 0, 12) . '...' : '-' }}</td>
                            <td class="d-flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('admin.backups.runs.verify', $run) }}">@csrf<button class="btn btn-sm btn-outline-secondary">Verificar</button></form>
                                <form method="POST" action="{{ route('admin.backups.runs.destroy', $run) }}" onsubmit="return confirm('Tem a certeza que pretende eliminar este backup do histórico?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Eliminar</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-secondary">Ainda não existem execuções de backup.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $runs->links() }}
    </div>
@endsection
