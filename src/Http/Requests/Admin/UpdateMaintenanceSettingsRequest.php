<?php

namespace Pcteckserv\CmsCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Pcteckserv\CmsCore\Services\Maintenance\MaintenanceTemplateRegistry;

class UpdateMaintenanceSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('maintenance.configure') ?? false;
    }

    public function rules(): array
    {
        return [
            'maintenance_enabled' => ['nullable', 'boolean'],
            'maintenance_template' => ['required', 'string', Rule::in(app(MaintenanceTemplateRegistry::class)->keys())],
            'maintenance_show_logo' => ['nullable', 'boolean'],
            'maintenance_logo_media_id' => ['nullable', 'integer', 'exists:cms_media,id'],
            'maintenance_title' => ['required', 'string', 'max:120'],
            'maintenance_message' => ['required', 'string', 'max:600'],
            'maintenance_secondary_text' => ['nullable', 'string', 'max:240'],
            'maintenance_schedule_enabled' => ['nullable', 'boolean'],
            'maintenance_start_at' => ['nullable', 'date'],
            'maintenance_end_at' => ['nullable', 'date', 'after_or_equal:maintenance_start_at'],
            'maintenance_auto_disable' => ['nullable', 'boolean'],
            'maintenance_show_countdown' => ['nullable', 'boolean'],
            'maintenance_show_footer' => ['nullable', 'boolean'],
            'maintenance_hero_media_id' => ['nullable', 'integer', 'exists:cms_media,id'],
            'maintenance_background_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'maintenance_text_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'maintenance_accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'maintenance_button_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'maintenance_access_enabled' => ['nullable', 'boolean'],
            'maintenance_access_code' => ['nullable', 'string', 'min:6', 'max:80'],
            'generate_maintenance_access_code' => ['nullable', 'boolean'],
            'maintenance_access_duration' => ['required', Rule::in(['1h', '6h', '12h', '24h', '3d', '7d', 'until_end'])],
            'invalidate_maintenance_access' => ['nullable', 'boolean'],
            'maintenance_admin_bypass' => ['nullable', 'boolean'],
            'maintenance_allowed_ips' => ['nullable', 'string', 'max:1200'],
            'maintenance_allowed_paths' => ['nullable', 'string', 'max:1200'],
            'maintenance_load_analytics' => ['nullable', 'boolean'],
        ];
    }

    public function validated($key = null, $default = null): mixed
    {
        $validated = parent::validated($key, $default);

        if ($key !== null) {
            return $validated;
        }

        foreach ([
            'maintenance_enabled',
            'maintenance_show_logo',
            'maintenance_schedule_enabled',
            'maintenance_auto_disable',
            'maintenance_show_countdown',
            'maintenance_show_footer',
            'maintenance_access_enabled',
            'generate_maintenance_access_code',
            'invalidate_maintenance_access',
            'maintenance_admin_bypass',
            'maintenance_load_analytics',
        ] as $field) {
            $validated[$field] = $this->boolean($field);
        }

        $validated['maintenance_logo_media_id'] = $validated['maintenance_logo_media_id'] ?? null;
        $validated['maintenance_hero_media_id'] = $validated['maintenance_hero_media_id'] ?? null;
        $validated['maintenance_secondary_text'] = trim((string) ($validated['maintenance_secondary_text'] ?? ''));
        $validated['maintenance_allowed_ips'] = $this->sanitizeIps((string) ($validated['maintenance_allowed_ips'] ?? ''));
        $validated['maintenance_allowed_paths'] = $this->sanitizePaths((string) ($validated['maintenance_allowed_paths'] ?? ''));

        if (! $validated['maintenance_schedule_enabled']) {
            $validated['maintenance_start_at'] = null;
            $validated['maintenance_end_at'] = null;
            $validated['maintenance_auto_disable'] = false;
        }

        return $validated;
    }

    private function sanitizeIps(string $value): string
    {
        $ips = array_filter(array_map('trim', preg_split('/\R/', $value) ?: []));
        $valid = array_filter($ips, static fn (string $ip): bool => filter_var($ip, FILTER_VALIDATE_IP) !== false);

        return implode("\n", array_values($valid));
    }

    private function sanitizePaths(string $value): string
    {
        $paths = array_filter(array_map('trim', preg_split('/\R/', $value) ?: []));
        $valid = array_filter($paths, static function (string $path): bool {
            $path = trim($path, '/');

            return $path !== ''
                && ! str_contains($path, '..')
                && ! str_starts_with($path, 'admin')
                && ! str_starts_with($path, 'http:')
                && ! str_starts_with($path, 'https:');
        });

        return implode("\n", array_map(static fn (string $path): string => trim($path, '/'), array_values($valid)));
    }
}
