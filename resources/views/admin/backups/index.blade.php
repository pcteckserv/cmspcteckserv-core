@extends('cms-core::admin.layouts.app', ['title' => 'Backups'])

@php
    $events = array_replace(config('cms-backups.notifications.events', []), $plan->notification_events ?: []);
@endphp

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger" role="alert"><p class="mb-1">Verifique os campos indicados:</p><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
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
        <div class="col-12 col-xl-6">
            <form class="bg-white border rounded-2 p-4 h-100" method="POST" action="{{ route('admin.backups.destinations.update', $destination) }}">
                @csrf
                @method('PUT')

                @include('cms-core::admin.backups.destination')
            </form>

            <form id="backup-destination-test-form" method="POST" class="d-none" action="{{ route('admin.backups.destinations.test', $destination) }}">
                @csrf
            </form>
        </div>

        <div class="col-xl-6">
            <form class="bg-white border rounded-2 p-4" method="POST" action="{{ route('admin.backups.plans.update', $plan) }}">
                @csrf
                @method('PUT')

                <h2 class="h5 mb-3">2. O que guardar e quantos conservar?</h2>

                <div class="form-check form-switch mb-3">
                    <input id="enabled" name="enabled" type="checkbox" value="1" class="form-check-input" @checked(old('enabled', $plan->enabled))>
                    <label class="form-check-label" for="enabled">Backup automático agendado</label>
                </div>

                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label" for="name">Plano</label><input id="name" name="name" class="form-control" value="{{ old('name', $plan->name) }}" required></div>
                    <div class="col-md-6"><label class="form-label" for="type">Tipo</label><select id="type" name="type" class="form-select">@foreach(['full'=>'Completo','images'=>'Apenas imagens','database'=>'Apenas base de dados','files'=>'Apenas ficheiros (avançado)','media'=>'Media e uploads (compatibilidade)'] as $value=>$label)<option value="{{ $value }}" @selected(old('type', $plan->type)===$value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label" for="frequency">Frequência</label><select id="frequency" name="frequency" class="form-select">@foreach(['daily'=>'Diário','weekly'=>'Semanal','weekdays'=>'Vários dias da semana','monthly'=>'Mensal','hourly'=>'De hora a hora','every_2_hours'=>'A cada 2 horas','every_3_hours'=>'A cada 3 horas','every_4_hours'=>'A cada 4 horas','every_6_hours'=>'A cada 6 horas','every_8_hours'=>'A cada 8 horas','every_12_hours'=>'A cada 12 horas'] as $value=>$label)<option value="{{ $value }}" @selected(old('frequency', $plan->frequency)===$value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label" for="run_at">Hora</label><input id="run_at" name="run_at" type="time" class="form-control" value="{{ old('run_at', substr((string) $plan->run_at, 0, 5)) }}" required></div>
                    <div class="col-md-3" data-monthly-field><label class="form-label" for="month_day">Dia do mês</label><input id="month_day" name="month_day" type="number" min="1" max="31" class="form-control" value="{{ old('month_day', $plan->month_day) }}"></div>
                    <div class="col-md-6"><label class="form-label" for="storage_mode">Guardar</label><select id="storage_mode" name="storage_mode" class="form-select">@foreach(['local_and_remote'=>'Local + remoto','local'=>'Apenas local','remote'=>'Apenas remoto'] as $value=>$label)<option value="{{ $value }}" @selected(old('storage_mode', $plan->storage_mode)===$value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label" for="retention_days">Eliminar após (dias, opcional)</label><input id="retention_days" name="retention_days" type="number" min="1" max="3650" class="form-control" value="{{ old('retention_days', $plan->retention_days) }}"></div>
                    <div class="col-md-3"><label class="form-label" for="retention_count">Backups a conservar</label><input id="retention_count" name="retention_count" type="number" min="1" class="form-control" value="{{ old('retention_count', $plan->retention_count ?? 7) }}"></div>
                    <input type="hidden" name="compression" value="zip">
                </div>

                <details class="mt-4">
                    <summary class="fw-semibold">Opções avançadas</summary>
                    <div class="row g-3 mt-2">
                        <div class="col-md-6"><label class="form-label" for="included_paths">Diretórios incluídos</label><textarea id="included_paths" name="included_paths" rows="5" class="form-control">{{ old('included_paths', implode("\n", $plan->included_paths ?: [])) }}</textarea></div>
                        <div class="col-md-6"><label class="form-label" for="excluded_paths">Diretórios excluídos</label><textarea id="excluded_paths" name="excluded_paths" rows="5" class="form-control">{{ old('excluded_paths', implode("\n", $plan->excluded_paths ?: [])) }}</textarea></div>
                    </div>
                    <div class="alert alert-warning mt-3 mb-0">As credenciais .env, dependências, cache e backups anteriores são excluídos. O backup completo inclui a base de dados e os ficheiros do site; as imagens e media incluem as bibliotecas públicas e privadas configuradas.</div>
                </details>

                <p class="form-text mt-3">A limpeza é automática após um backup bem-sucedido e na manutenção agendada. Conserva as cópias mais recentes e mantém sempre pelo menos uma. Backups protegidos não entram no limite.</p>
                <details class="mt-4"><summary class="fw-semibold">Notificações e alertas</summary>
                <label class="form-label" for="notification_emails">Destinatários dos alertas</label>
                <textarea id="notification_emails" name="notification_emails" rows="4" class="form-control" placeholder="admin@empresa.pt&#10;suporte@pcteckserv.com">{{ old('notification_emails', implode("\n", $plan->notification_emails ?: [])) }}</textarea>
                <div class="form-text mb-3">Introduza um email por linha. Não utilize vírgulas.</div>

                <div class="row g-2">
                    @foreach(['backup_failed'=>'Backup falhar','remote_upload_failed'=>'Upload remoto falhar','backup_missing'=>'Backup não executado','backup_corrupted'=>'Backup corrompido','backup_succeeded'=>'Backup concluído com sucesso','retention_deleted'=>'Retenção eliminar backups antigos','recovery'=>'Problema resolvido'] as $event=>$label)
                        @php $input = $event === 'recovery' ? 'notify_recovery' : 'notify_'.$event; @endphp
                        <div class="col-md-6"><div class="form-check"><input class="form-check-input" id="{{ $input }}" name="{{ $input }}" value="1" type="checkbox" @checked(old($input, $event === 'recovery' ? $plan->notify_recovery : ($events[$event] ?? false)))><label class="form-check-label" for="{{ $input }}">{{ $label }}</label></div></div>
                    @endforeach
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-6"><label class="form-label" for="alert_timing">Enviar alerta</label><select id="alert_timing" name="alert_timing" class="form-select"><option value="after_retries" @selected($plan->alert_timing==='after_retries')>Após esgotar tentativas</option><option value="first_failure" @selected($plan->alert_timing==='first_failure')>Na primeira falha</option></select></div>
                    <div class="col-md-6"><label class="form-label" for="repeat_alert_after_minutes">Repetir alerta a cada</label><select id="repeat_alert_after_minutes" name="repeat_alert_after_minutes" class="form-select">@foreach([0 => 'Nunca repetir', 60 => '1 hora', 360 => '6 horas', 720 => '12 horas', 1440 => '24 horas', 2880 => '2 dias', 10080 => '7 dias'] as $value => $label)<option value="{{ $value }}" @selected((int) old('repeat_alert_after_minutes', $plan->repeat_alert_after_minutes) === $value)>{{ $label }}</option>@endforeach</select></div>
                </div>

                </details>
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
                <select name="type" class="form-select"><option value="full">Completo</option><option value="database">Apenas base de dados</option><option value="images">Apenas imagens</option><option value="files">Apenas ficheiros (avançado)</option><option value="media">Media e uploads (compatibilidade)</option></select>
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
                            <td><span class="badge text-bg-{{ $run->status === 'success' ? 'success' : ($run->status === 'failed' ? 'danger' : 'warning') }}">{{ ['success'=>'Concluído','failed'=>'Falhou','partial'=>'Parcial','running'=>'Em execução','pending'=>'Em espera'][$run->status] ?? $run->status }}</span>@if($run->failure_reason)<div class="small text-danger text-break mt-1">{{ $run->failure_reason }}</div>@endif</td>
                            <td>{{ $run->size_bytes ? number_format($run->size_bytes / 1024 / 1024, 2, ',', ' ') . ' MB' : '-' }}</td>
                            <td>{{ $run->duration_seconds ? $run->duration_seconds . ' s' : '-' }}</td>
                            <td class="small">{{ $run->checksum_sha256 ? substr($run->checksum_sha256, 0, 12) . '...' : '-' }}</td>
                            <td class="d-flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('admin.backups.runs.verify', $run) }}">@csrf<button class="btn btn-sm btn-outline-secondary">Verificar</button></form>
                                <form method="POST" action="{{ route('admin.backups.runs.destroy', $run) }}" onsubmit="return confirm('Eliminar este backup e os seus ficheiros locais e remotos?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger cms-media-icon-button" type="submit" title="Eliminar" aria-label="Eliminar">@include('cms-core::components.icons.trash')</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-secondary">Ainda não existem execuções de backup.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $runs->links('cms-core::admin.partials.pagination') }}
    </div>

    <form id="backup-google-connect" method="POST" action="{{ route('admin.backups.google.connect', $destination) }}">@csrf</form>
    <form id="backup-google-disconnect" method="POST" action="{{ route('admin.backups.google.disconnect', $destination) }}">@csrf @method('DELETE')</form>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const protocol = document.getElementById('protocol');
            const syncDestination = () => {
                document.querySelectorAll('[data-backup-protocols]').forEach(section => {
                    const active = section.dataset.backupProtocols.split(' ').includes(protocol.value);
                    section.hidden = !active;
                    section.querySelectorAll('input,select,textarea,button').forEach(input => input.disabled = !active);
                });
            };
            protocol?.addEventListener('change', () => {
                syncDestination();
                const advanced = document.querySelector('[data-backup-advanced]');
                if (advanced) advanced.open = protocol.value !== 'google';
            });
            if (protocol) syncDestination();
            const frequency = document.getElementById('frequency');
            const monthlyField = document.querySelector('[data-monthly-field]');
            const monthDay = document.getElementById('month_day');

            if (!frequency || !monthlyField || !monthDay) {
                return;
            }

            const syncMonthlyField = () => {
                const isMonthly = frequency.value === 'monthly';
                monthlyField.classList.toggle('d-none', !isMonthly);
                monthDay.disabled = !isMonthly;
            };

            frequency.addEventListener('change', syncMonthlyField);
            syncMonthlyField();
        });
    </script>
@endsection
