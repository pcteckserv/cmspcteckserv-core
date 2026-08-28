<?php

namespace Pcteckserv\CmsCore\ActivityLog;

class Sanitizer
{
    public function sanitize(array $values): array
    {
        return $this->sanitizeValue($values);
    }

    private function sanitizeValue(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $sanitized = [];

        foreach ($value as $key => $item) {
            $sanitized[$key] = $this->isSensitiveKey((string) $key)
                ? '[REMOVIDO]'
                : $this->sanitizeValue($item);
        }

        return $sanitized;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower(str_replace(['-', ' '], '_', $key));

        foreach ((array) config('cms-core.activity_log.sensitive_fields', []) as $field) {
            $field = strtolower(str_replace(['-', ' '], '_', (string) $field));

            if ($normalized === $field || str_contains($normalized, $field)) {
                return true;
            }
        }

        return false;
    }
}
