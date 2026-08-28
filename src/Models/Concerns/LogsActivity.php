<?php

namespace Pcteckserv\CmsCore\Models\Concerns;

use Pcteckserv\CmsCore\ActivityLog\Contracts\ActivityLoggerContract;

trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(fn ($model) => $model->recordActivity('created'));
        static::updated(fn ($model) => $model->recordActivity('updated'));
        static::deleted(fn ($model) => $model->recordActivity('deleted'));
    }

    public function auditedFields(): array
    {
        return property_exists($this, 'auditFields') ? $this->auditFields : [];
    }

    protected function recordActivity(string $event): void
    {
        $fields = $this->auditedFields();

        if ($event === 'updated' && $fields === []) {
            return;
        }

        $oldValues = [];
        $newValues = [];

        foreach ($fields as $field) {
            if ($event === 'updated' && ! $this->wasChanged($field)) {
                continue;
            }

            $oldValues[$field] = $event === 'created' ? null : $this->getOriginal($field);
            $newValues[$field] = $event === 'deleted' ? null : $this->getAttribute($field);
        }

        if ($fields !== [] && $event === 'updated' && $oldValues === [] && $newValues === []) {
            return;
        }

        app(ActivityLoggerContract::class)->log(
            action: $this->activityLogAction($event),
            category: $this->activityLogCategory(),
            description: $this->activityLogDescription($event),
            subject: $this,
            oldValues: $oldValues,
            newValues: $newValues,
        );
    }

    protected function activityLogAction(string $event): string
    {
        return str(class_basename($this))->kebab()->toString().'.'.$event;
    }

    protected function activityLogCategory(): string
    {
        return str(class_basename($this))->plural()->kebab()->toString();
    }

    protected function activityLogDescription(string $event): string
    {
        return match ($event) {
            'created' => class_basename($this).' criado.',
            'updated' => class_basename($this).' atualizado.',
            'deleted' => class_basename($this).' eliminado.',
            default => class_basename($this).' alterado.',
        };
    }
}
