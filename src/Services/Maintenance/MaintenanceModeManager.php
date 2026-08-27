<?php

namespace Pcteckserv\CmsCore\Services\Maintenance;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Pcteckserv\CmsCore\Contracts\MediaUrlGenerator;
use Pcteckserv\CmsCore\Events\MaintenanceAccessGranted;
use Pcteckserv\CmsCore\Events\MaintenanceAccessRevoked;
use Pcteckserv\CmsCore\Events\MaintenanceModeDisabled;
use Pcteckserv\CmsCore\Events\MaintenanceModeEnabled;
use Pcteckserv\CmsCore\Models\Media;
use Pcteckserv\CmsCore\Support\SiteOptions;

class MaintenanceModeManager
{
    public const SESSION_KEY = 'cms_maintenance_access';
    public const INTENDED_URL_KEY = 'cms_maintenance_intended_url';

    public function __construct(
        private readonly SiteOptions $siteOptions,
        private readonly MediaUrlGenerator $mediaUrlGenerator,
        private readonly MaintenanceTemplateRegistry $templates,
    ) {}

    public function settings(): array
    {
        $options = $this->options();
        $template = $this->templates->get((string) ($options['maintenance_template'] ?? 'minimal'));

        return [
            'enabled' => $this->truthy($options['maintenance_enabled'] ?? false),
            'template' => $template->key,
            'template_name' => $template->name,
            'template_view' => $template->view,
            'site_title' => (string) ($options['site_title'] ?? config('app.name', 'CMS PCTECK')),
            'site_icon_url' => (string) ($options['site_icon_url'] ?? ''),
            'logo_media_id' => ($options['maintenance_logo_media_id'] ?? null) ?: null,
            'logo_url' => $this->mediaUrl($options['maintenance_logo_media_id'] ?? null),
            'logo_scale' => $this->integer($options['maintenance_logo_scale'] ?? null, 100, 25, 250),
            'logo_max_width' => $this->scaledPixels($options['maintenance_logo_scale'] ?? null, 180, '180px'),
            'logo_max_height' => $this->scaledPixels($options['maintenance_logo_scale'] ?? null, 72, '72px'),
            'title' => (string) ($options['maintenance_title'] ?? 'Estamos a preparar algo novo.'),
            'message' => (string) ($options['maintenance_message'] ?? 'O nosso site encontra-se temporariamente em manutenção. Voltamos em breve.'),
            'secondary_text' => (string) ($options['maintenance_secondary_text'] ?? ''),
            'schedule_enabled' => $this->truthy($options['maintenance_schedule_enabled'] ?? false),
            'start_at' => $this->date($options['maintenance_start_at'] ?? null),
            'end_at' => $this->date($options['maintenance_end_at'] ?? null),
            'auto_disable' => $this->truthy($options['maintenance_auto_disable'] ?? true),
            'show_countdown' => $this->truthy($options['maintenance_show_countdown'] ?? true),
            'show_footer' => $this->truthy($options['maintenance_show_footer'] ?? true),
            'hero_media_id' => ($options['maintenance_hero_media_id'] ?? null) ?: null,
            'hero_url' => $this->mediaUrl($options['maintenance_hero_media_id'] ?? null),
            'background_color' => $this->color($options['maintenance_background_color'] ?? null, '#0C0C0C'),
            'text_color' => $this->color($options['maintenance_text_color'] ?? null, '#FFFFFF'),
            'accent_color' => $this->color($options['maintenance_accent_color'] ?? null, '#0D6EFD'),
            'button_color' => $this->color($options['maintenance_button_color'] ?? null, '#0D6EFD'),
            'access_enabled' => $this->truthy($options['maintenance_access_enabled'] ?? false),
            'access_code_configured' => filled($options['maintenance_access_code_hash'] ?? null),
            'access_version' => max(1, (int) ($options['maintenance_access_version'] ?? 1)),
            'access_duration' => (string) ($options['maintenance_access_duration'] ?? '24h'),
            'admin_bypass' => $this->truthy($options['maintenance_admin_bypass'] ?? true),
            'allowed_ips' => $this->lines($options['maintenance_allowed_ips'] ?? ''),
            'allowed_paths' => $this->lines($options['maintenance_allowed_paths'] ?? ''),
            'load_analytics' => $this->truthy($options['maintenance_load_analytics'] ?? false),
            'timezone' => config('app.timezone', 'Europe/Lisbon'),
        ];
    }

    public function options(): array
    {
        return array_replace($this->fallbackOptions(), $this->siteOptions->all());
    }

    public function isActive(): bool
    {
        $settings = $this->settings();
        $now = now();

        if (! $settings['enabled']) {
            return false;
        }

        if ($settings['schedule_enabled'] && $settings['start_at'] && $settings['start_at']->isFuture()) {
            return false;
        }

        if ($settings['schedule_enabled'] && $settings['auto_disable'] && $settings['end_at'] && $settings['end_at']->isPast()) {
            return false;
        }

        return true;
    }

    public function update(array $settings, ?int $userId = null): ?string
    {
        $payload = $settings;
        $plainCode = trim((string) ($payload['maintenance_access_code'] ?? ''));
        $generatedCode = null;
        unset($payload['maintenance_access_code']);

        if (($payload['generate_maintenance_access_code'] ?? false) || $plainCode !== '') {
            $generatedCode = $plainCode !== '' ? $plainCode : $this->generateAccessCode();
            $payload['maintenance_access_code_hash'] = Hash::make($generatedCode);
        }

        unset($payload['generate_maintenance_access_code']);

        if ($this->truthy($payload['invalidate_maintenance_access'] ?? false)) {
            $payload['maintenance_access_version'] = $this->nextAccessVersion();
        }

        unset($payload['invalidate_maintenance_access']);
        $this->siteOptions->setMany($payload);
        $this->audit('updated', $userId);

        return $generatedCode;
    }

    public function enable(?int $userId = null): void
    {
        $this->siteOptions->setMany(['maintenance_enabled' => true]);
        $this->audit('enabled', $userId);
        event(new MaintenanceModeEnabled($userId));
    }

    public function disable(?int $userId = null): void
    {
        $this->siteOptions->setMany(['maintenance_enabled' => false]);
        $this->audit('disabled', $userId);
        event(new MaintenanceModeDisabled($userId));
    }

    public function revokeAccess(?int $userId = null): void
    {
        $this->siteOptions->setMany(['maintenance_access_version' => $this->nextAccessVersion()]);
        $this->audit('access_revoked', $userId);
        event(new MaintenanceAccessRevoked($userId));
    }

    public function grantAccess(Request $request): void
    {
        $settings = $this->settings();
        $request->session()->put(self::SESSION_KEY, [
            'version' => $settings['access_version'],
            'expires_at' => $this->accessExpiresAt($settings)->timestamp,
        ]);
        $this->audit('access_granted');
        event(new MaintenanceAccessGranted());
    }

    public function hasTemporaryAccess(Request $request): bool
    {
        $access = $request->session()->get(self::SESSION_KEY);
        $settings = $this->settings();

        if (! is_array($access) || (int) ($access['version'] ?? 0) !== $settings['access_version']) {
            return false;
        }

        return (int) ($access['expires_at'] ?? 0) > now()->timestamp;
    }

    public function codeIsValid(string $code): bool
    {
        $hash = (string) $this->siteOptions->get('maintenance_access_code_hash', '');

        return $hash !== '' && Hash::check($code, $hash);
    }

    public function applySchedule(): void
    {
        $settings = $this->settings();
        $now = now();

        if (! $settings['schedule_enabled']) {
            return;
        }

        if (! $settings['enabled'] && $settings['start_at'] && $settings['start_at']->lessThanOrEqualTo($now)) {
            $this->enable();
        }

        if ($settings['enabled'] && $settings['auto_disable'] && $settings['end_at'] && $settings['end_at']->lessThanOrEqualTo($now)) {
            $this->disable();
        }
    }

    public function generateAccessCode(): string
    {
        return strtoupper(Str::random(4).'-'.Str::random(4));
    }

    private function nextAccessVersion(): int
    {
        return max(1, (int) $this->siteOptions->get('maintenance_access_version', 1)) + 1;
    }

    private function accessExpiresAt(array $settings): CarbonImmutable
    {
        if ($settings['access_duration'] === 'until_end' && $settings['end_at']) {
            return CarbonImmutable::instance($settings['end_at']);
        }

        return match ($settings['access_duration']) {
            '1h' => now()->toImmutable()->addHour(),
            '6h' => now()->toImmutable()->addHours(6),
            '12h' => now()->toImmutable()->addHours(12),
            '3d' => now()->toImmutable()->addDays(3),
            '7d' => now()->toImmutable()->addDays(7),
            default => now()->toImmutable()->addDay(),
        };
    }

    private function mediaUrl(mixed $mediaId): ?string
    {
        $id = (int) $mediaId;

        if ($id <= 0) {
            return null;
        }

        $media = Media::query()->find($id);

        return $media ? $this->mediaUrlGenerator->url($media) : null;
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value, config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }
    }

    private function color(mixed $value, string $fallback): string
    {
        return is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value) ? strtoupper($value) : $fallback;
    }

    private function integer(mixed $value, int $fallback, int $min, int $max): int
    {
        $value = filter_var($value, FILTER_VALIDATE_INT);

        if ($value === false) {
            return $fallback;
        }

        return max($min, min($max, $value));
    }

    private function scaledPixels(mixed $scale, int $basePixels, string $fallback): string
    {
        $scale = $this->integer($scale, 100, 25, 250);
        $pixels = (int) round($basePixels * ($scale / 100));

        return $pixels > 0 ? $pixels.'px' : $fallback;
    }

    private function lines(mixed $value): array
    {
        if (! is_string($value)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/\R/', $value) ?: [])));
    }

    private function truthy(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function audit(string $action, ?int $userId = null): void
    {
        Log::info('CMS maintenance mode action', ['action' => $action, 'user_id' => $userId]);
    }

    private function fallbackOptions(): array
    {
        return [
            'maintenance_enabled' => false,
            'maintenance_template' => 'minimal',
            'maintenance_logo_media_id' => null,
            'maintenance_logo_scale' => 100,
            'maintenance_title' => 'Estamos a preparar algo novo.',
            'maintenance_message' => 'O nosso site encontra-se temporariamente em manutenção. Voltamos em breve.',
            'maintenance_secondary_text' => 'Agradecemos a sua compreensão.',
            'maintenance_schedule_enabled' => false,
            'maintenance_start_at' => null,
            'maintenance_end_at' => null,
            'maintenance_auto_disable' => true,
            'maintenance_show_countdown' => true,
            'maintenance_show_footer' => true,
            'maintenance_hero_media_id' => null,
            'maintenance_background_color' => '#0C0C0C',
            'maintenance_text_color' => '#FFFFFF',
            'maintenance_accent_color' => '#0D6EFD',
            'maintenance_button_color' => '#0D6EFD',
            'maintenance_access_enabled' => false,
            'maintenance_access_code_hash' => null,
            'maintenance_access_version' => 1,
            'maintenance_access_duration' => '24h',
            'maintenance_admin_bypass' => true,
            'maintenance_allowed_ips' => '',
            'maintenance_allowed_paths' => '',
            'maintenance_load_analytics' => false,
        ];
    }
}
