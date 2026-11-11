<?php

namespace Pcteckserv\CmsCore\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Pcteckserv\CmsCore\Support\SiteUrl;

final class SiteLink implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $urls = app(SiteUrl::class);
        if (! is_string($value) || ! $urls->isSafe($value)
            || (! $urls->isInternalPath($value) && ! filter_var($value, FILTER_VALIDATE_URL))) {
            $fail('Indique um caminho interno, como /contactos, ou um URL HTTP ou HTTPS sem credenciais.');
        }
    }
}
