<?php

namespace Pcteckserv\CmsCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBackupPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('backups.configure') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'enabled' => ['nullable', 'boolean'],
            'type' => ['required', Rule::in(['database', 'files', 'media', 'full'])],
            'frequency' => ['required', Rule::in(['every_15_minutes', 'every_30_minutes', 'hourly', 'every_2_hours', 'every_3_hours', 'every_4_hours', 'every_6_hours', 'every_8_hours', 'every_12_hours', 'daily', 'weekly', 'weekdays', 'monthly'])],
            'run_at' => ['required', 'date_format:H:i'],
            'weekdays' => ['nullable', 'array'],
            'weekdays.*' => ['integer', 'between:1,7'],
            'month_day' => ['nullable', 'integer', 'between:1,31'],
            'retention_days' => ['nullable', 'integer', 'between:1,3650'],
            'retention_count' => ['nullable', 'integer', 'between:1,10000'],
            'compression' => ['required', Rule::in(['zip'])],
            'storage_mode' => ['required', Rule::in(['local', 'remote', 'local_and_remote'])],
            'included_paths' => ['nullable', 'string'],
            'excluded_paths' => ['nullable', 'string'],
            'notification_emails' => ['nullable', 'string'],
            'notify_backup_failed' => ['nullable', 'boolean'],
            'notify_backup_missing' => ['nullable', 'boolean'],
            'notify_backup_corrupted' => ['nullable', 'boolean'],
            'notify_remote_upload_failed' => ['nullable', 'boolean'],
            'notify_backup_succeeded' => ['nullable', 'boolean'],
            'notify_retention_deleted' => ['nullable', 'boolean'],
            'notify_recovery' => ['nullable', 'boolean'],
            'alert_timing' => ['required', Rule::in(['after_retries', 'first_failure'])],
            'repeat_alert_after_minutes' => ['required', 'integer', 'between:15,10080'],
        ];
    }

    public function validated($key = null, $default = null): mixed
    {
        $validated = parent::validated($key, $default);

        if ($key !== null) {
            return $validated;
        }

        $emails = $this->lines('notification_emails');
        validator(['emails' => $emails], ['emails.*' => ['email', 'max:255']])->validate();

        $validated['enabled'] = $this->boolean('enabled');
        $validated['notify_recovery'] = $this->boolean('notify_recovery');
        $validated['included_paths'] = $this->lines('included_paths');
        $validated['excluded_paths'] = $this->lines('excluded_paths');
        $validated['notification_emails'] = $emails;
        $validated['notification_events'] = [
            'backup_failed' => $this->boolean('notify_backup_failed'),
            'backup_missing' => $this->boolean('notify_backup_missing'),
            'backup_corrupted' => $this->boolean('notify_backup_corrupted'),
            'remote_upload_failed' => $this->boolean('notify_remote_upload_failed'),
            'backup_succeeded' => $this->boolean('notify_backup_succeeded'),
            'retention_deleted' => $this->boolean('notify_retention_deleted'),
            'recovered' => $this->boolean('notify_recovery'),
        ];

        return $validated;
    }

    private function lines(string $key): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R+/', (string) $this->input($key, '')) ?: [])));
    }
}
