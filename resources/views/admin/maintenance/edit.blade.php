@extends('cms-core::admin.layouts.app', ['title' => 'Modo de Manutenção'])

@section('content')
    @php($maintenanceScheduleEnabled = old('maintenance_schedule_enabled', $options['maintenance_schedule_enabled'] ?? false))
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Modo de Manutenção</h1>
            <p class="text-secondary mb-0">Modo de manutenção público do CMS. A administração continua acessível.</p>
        </div>
        @can('maintenance.preview')
            <a class="btn btn-outline-primary" href="{{ route('admin.maintenance.preview') }}" target="_blank" rel="noopener">Abrir pré-visualização</a>
        @endcan
    </div>

    @if (session('cms_maintenance_success'))
        <div class="alert alert-success">{{ session('cms_maintenance_success') }}</div>
    @endif

    @if (session('cms_maintenance_access_code'))
        <div class="alert alert-info d-flex flex-column flex-md-row align-items-md-center gap-3">
            <div>
                <strong>Novo código de acesso:</strong>
                <code id="cms-maintenance-generated-code">{{ session('cms_maintenance_access_code') }}</code>
                <div class="small">Copie agora. Depois de sair desta página o código completo não volta a ser apresentado.</div>
            </div>
            <button class="btn btn-outline-primary btn-sm ms-md-auto" type="button" data-copy-target="#cms-maintenance-generated-code">Copiar código</button>
        </div>
    @endif

    <form class="bg-white border rounded-2 p-4 cms-settings-form" method="POST" action="{{ route('admin.maintenance.update') }}" data-cms-maintenance-form>
        @csrf
        @method('PUT')

        <div class="row g-3 align-items-start mb-4">
            <div class="col-lg-2">
                <h2 class="h5 mb-1">Estado</h2>
            </div>
            <div class="col-lg-10">
                <div class="form-check form-switch mb-2">
                    <input type="hidden" name="maintenance_enabled" value="0">
                    <input id="maintenance_enabled" name="maintenance_enabled" type="checkbox" class="form-check-input" value="1" @checked(old('maintenance_enabled', $options['maintenance_enabled']))>
                    <label class="form-check-label fw-semibold" for="maintenance_enabled">Modo de Manutenção</label>
                </div>
                <div class="form-text">Quando activo, visitantes da área pública recebem a página temporária com HTTP 503.</div>
            </div>
        </div>

        <div class="row g-3 align-items-start mb-4">
            <div class="col-lg-2">
                <h2 class="h5 mb-1">Agendamento</h2>
            </div>
            <div class="col-lg-10">
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="maintenance_schedule_enabled" value="0">
                    <input id="maintenance_schedule_enabled" name="maintenance_schedule_enabled" type="checkbox" class="form-check-input" value="1" @checked($maintenanceScheduleEnabled) data-maintenance-schedule-toggle>
                    <label class="form-check-label" for="maintenance_schedule_enabled">Ativar programação da manutenção</label>
                </div>
                <div class="row g-3 {{ $maintenanceScheduleEnabled ? '' : 'd-none' }}" data-maintenance-schedule-fields>
                    <div class="col-md-6">
                        <label class="form-label" for="maintenance_start_at">Início da manutenção</label>
                        <input id="maintenance_start_at" name="maintenance_start_at" type="datetime-local" class="form-control @error('maintenance_start_at') is-invalid @enderror" value="{{ old('maintenance_start_at', $maintenance['start_at']?->format('Y-m-d\TH:i')) }}" @disabled(! $maintenanceScheduleEnabled)>
                        @error('maintenance_start_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="maintenance_end_at">Fim previsto</label>
                        <input id="maintenance_end_at" name="maintenance_end_at" type="datetime-local" class="form-control @error('maintenance_end_at') is-invalid @enderror" value="{{ old('maintenance_end_at', $maintenance['end_at']?->format('Y-m-d\TH:i')) }}" @disabled(! $maintenanceScheduleEnabled)>
                        @error('maintenance_end_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <input type="hidden" name="maintenance_auto_disable" value="{{ $maintenanceScheduleEnabled ? 1 : 0 }}" data-maintenance-auto-disable>
                <div class="form-text">Timezone: {{ $maintenance['timezone'] }}</div>
            </div>
        </div>

        <div class="row g-3 align-items-start mb-4">
            <div class="col-lg-2">
                <h2 class="h5 mb-1">Template</h2>
            </div>
            <div class="col-lg-10">
                <div class="row g-3">
                    @foreach ($templates as $template)
                        <div class="col-md-4">
                            <label @class(['cms-maintenance-template-card', 'is-selected' => old('maintenance_template', $maintenance['template']) === $template->key])>
                                <input class="form-check-input" type="radio" name="maintenance_template" value="{{ $template->key }}" @checked(old('maintenance_template', $maintenance['template']) === $template->key)>
                                <span class="fw-semibold">{{ $template->name }}</span>
                                <span class="text-secondary small">{{ $template->description }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="row g-3 align-items-start mb-4">
            <div class="col-lg-2">
                <h2 class="h5 mb-1">Conteúdo</h2>
            </div>
            <div class="col-lg-10">
                <div class="row g-3">
                    <x-cms-media-picker class="col-md-6" name="maintenance_logo_media_id" id="maintenance_logo_media_id" label="Logótipo" :value="$options['maintenance_logo_media_id']" help="Opcional. Se não escolher uma imagem, o logótipo não é apresentado." clearable />
                    <x-cms-media-picker class="col-md-6" name="maintenance_hero_media_id" id="maintenance_hero_media_id" label="Imagem Hero" :value="$options['maintenance_hero_media_id']" help="Usada pelo template Hero." clearable />
                    <div class="col-md-12">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <label class="form-label mb-0" for="maintenance_logo_scale">Tamanho do logótipo</label>
                            <output class="badge text-bg-light border" for="maintenance_logo_scale" data-cms-maintenance-logo-scale-output>{{ old('maintenance_logo_scale', $options['maintenance_logo_scale'] ?? 100) }}%</output>
                        </div>
                        <input id="maintenance_logo_scale" name="maintenance_logo_scale" type="range" min="25" max="250" step="5" class="form-range @error('maintenance_logo_scale') is-invalid @enderror" value="{{ old('maintenance_logo_scale', $options['maintenance_logo_scale'] ?? 100) }}" required data-cms-maintenance-logo-scale>
                        <div class="form-text">Aumenta ou reduz proporcionalmente o logótipo na página de manutenção.</div>
                        @error('maintenance_logo_scale')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-12">
                        <label class="form-label" for="maintenance_title">Título</label>
                        <input id="maintenance_title" name="maintenance_title" type="text" class="form-control @error('maintenance_title') is-invalid @enderror" value="{{ old('maintenance_title', $options['maintenance_title']) }}" required>
                        @error('maintenance_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-12">
                        <label class="form-label" for="maintenance_message">Mensagem</label>
                        <textarea id="maintenance_message" name="maintenance_message" rows="4" class="form-control @error('maintenance_message') is-invalid @enderror" required>{{ old('maintenance_message', $options['maintenance_message']) }}</textarea>
                        @error('maintenance_message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-12">
                        <label class="form-label" for="maintenance_secondary_text">Texto secundário</label>
                        <input id="maintenance_secondary_text" name="maintenance_secondary_text" type="text" class="form-control @error('maintenance_secondary_text') is-invalid @enderror" value="{{ old('maintenance_secondary_text', $options['maintenance_secondary_text']) }}">
                        @error('maintenance_secondary_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="form-check form-switch mt-3">
                    <input type="hidden" name="maintenance_show_countdown" value="0">
                    <input id="maintenance_show_countdown" name="maintenance_show_countdown" type="checkbox" class="form-check-input" value="1" @checked(old('maintenance_show_countdown', $options['maintenance_show_countdown']))>
                    <label class="form-check-label" for="maintenance_show_countdown">Mostrar contador</label>
                </div>
                <div class="form-check form-switch mt-2">
                    <input type="hidden" name="maintenance_show_footer" value="0">
                    <input id="maintenance_show_footer" name="maintenance_show_footer" type="checkbox" class="form-check-input" value="1" @checked(old('maintenance_show_footer', $options['maintenance_show_footer']))>
                    <label class="form-check-label" for="maintenance_show_footer">Mostrar footer</label>
                </div>
            </div>
        </div>

        <div class="row g-3 align-items-start mb-4">
            <div class="col-lg-2">
                <h2 class="h5 mb-1">Aparência</h2>
            </div>
            <div class="col-lg-10">
                <div class="row g-3">
                    @foreach ([
                        'maintenance_background_color' => 'Cor de fundo',
                        'maintenance_text_color' => 'Cor do texto',
                        'maintenance_accent_color' => 'Cor principal',
                        'maintenance_button_color' => 'Cor dos botões',
                    ] as $field => $label)
                        <div class="col-md-3">
                            <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                            <input id="{{ $field }}" name="{{ $field }}" type="color" class="form-control form-control-color @error($field) is-invalid @enderror" value="{{ old($field, $options[$field]) }}" required>
                            @error($field)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="row g-3 align-items-start mb-4">
            <div class="col-lg-2">
                <h2 class="h5 mb-1">Acesso privado</h2>
            </div>
            <div class="col-lg-10">
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="maintenance_access_enabled" value="0">
                    <input id="maintenance_access_enabled" name="maintenance_access_enabled" type="checkbox" class="form-check-input" value="1" @checked(old('maintenance_access_enabled', $options['maintenance_access_enabled']))>
                    <label class="form-check-label" for="maintenance_access_enabled">Permitir acesso através de código</label>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="maintenance_access_code">Código de acesso</label>
                        <input id="maintenance_access_code" name="maintenance_access_code" type="text" class="form-control @error('maintenance_access_code') is-invalid @enderror" value="{{ old('maintenance_access_code', session('cms_maintenance_access_code', $maintenance['access_code'])) }}" placeholder="Definir código">
                        <div class="form-text">O código é validado por hash e guardado encriptado para consulta no admin.</div>
                        @error('maintenance_access_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="maintenance_access_duration">Duração</label>
                        <select id="maintenance_access_duration" name="maintenance_access_duration" class="form-select">
                            @foreach (['1h' => '1 hora', '6h' => '6 horas', '12h' => '12 horas', '24h' => '24 horas', '3d' => '3 dias', '7d' => '7 dias', 'until_end' => 'Até terminar'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('maintenance_access_duration', $options['maintenance_access_duration']) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <input type="hidden" name="generate_maintenance_access_code" value="0">
                        <button class="btn btn-outline-primary w-100" type="submit" name="generate_maintenance_access_code" value="1">Gerar novo</button>
                    </div>
                </div>
                <div class="form-check mt-3">
                    <input type="hidden" name="invalidate_maintenance_access" value="0">
                    <input id="invalidate_maintenance_access" name="invalidate_maintenance_access" class="form-check-input" type="checkbox" value="1" checked>
                    <label class="form-check-label" for="invalidate_maintenance_access">Invalidar acessos existentes ao alterar o código</label>
                </div>
            </div>
        </div>

        <div class="row g-3 align-items-start mb-4">
            <div class="col-lg-2">
                <h2 class="h5 mb-1">Exceções</h2>
            </div>
            <div class="col-lg-10">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="maintenance_allowed_ips">IPs autorizados</label>
                        <textarea id="maintenance_allowed_ips" name="maintenance_allowed_ips" rows="4" class="form-control">{{ old('maintenance_allowed_ips', $options['maintenance_allowed_ips']) }}</textarea>
                        <div class="form-text">Um IP IPv4/IPv6 por linha.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="maintenance_allowed_paths">URLs públicas permitidas</label>
                        <textarea id="maintenance_allowed_paths" name="maintenance_allowed_paths" rows="4" class="form-control">{{ old('maintenance_allowed_paths', $options['maintenance_allowed_paths']) }}</textarea>
                        <div class="form-text">Um path relativo por linha. `/admin` nunca é aceite aqui.</div>
                    </div>
                </div>
                <div class="form-check form-switch mt-3">
                    <input type="hidden" name="maintenance_admin_bypass" value="0">
                    <input id="maintenance_admin_bypass" name="maintenance_admin_bypass" type="checkbox" class="form-check-input" value="1" @checked(old('maintenance_admin_bypass', $options['maintenance_admin_bypass']))>
                    <label class="form-check-label" for="maintenance_admin_bypass">Administradores autorizados podem visualizar o site</label>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="offset-lg-2 col-lg-10 d-flex flex-wrap gap-2">
                <button class="btn btn-primary" type="submit">Guardar alterações</button>
                @can('maintenance.manage-access')
                    <button class="btn btn-outline-danger" type="submit" formaction="{{ route('admin.maintenance.revoke-access') }}" formmethod="POST">Revogar acessos temporários</button>
                @endcan
            </div>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggle = document.querySelector('[data-maintenance-schedule-toggle]');
            const fields = document.querySelector('[data-maintenance-schedule-fields]');
            const autoDisable = document.querySelector('[data-maintenance-auto-disable]');
            const logoScale = document.querySelector('[data-cms-maintenance-logo-scale]');
            const logoScaleOutput = document.querySelector('[data-cms-maintenance-logo-scale-output]');

            if (! toggle || ! fields || ! autoDisable) {
                return;
            }

            const syncScheduleFields = () => {
                fields.classList.toggle('d-none', ! toggle.checked);
                autoDisable.value = toggle.checked ? '1' : '0';

                fields.querySelectorAll('input').forEach((input) => {
                    input.disabled = ! toggle.checked;
                });
            };

            toggle.addEventListener('change', syncScheduleFields);
            syncScheduleFields();

            logoScale?.addEventListener('input', () => {
                if (logoScaleOutput) {
                    logoScaleOutput.value = `${logoScale.value}%`;
                    logoScaleOutput.textContent = `${logoScale.value}%`;
                }
            });
        });
    </script>
@endsection
