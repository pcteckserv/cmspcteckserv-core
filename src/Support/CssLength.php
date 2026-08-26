<?php

namespace Pcteckserv\CmsCore\Support;

class CssLength
{
    public const PATTERN = '/^(?:0|\d+(?:\.\d+)?(?:px|%|rem|em|vh|vw|vmin|vmax|ch|ex|cm|mm|in|pt|pc)|(?:auto|min-content|max-content|fit-content)|(?:calc|clamp|min|max)\([A-Za-z0-9\s+\-*\/().,%]+\))$/';

    public static function normalize(mixed $value, string $fallback): string
    {
        $value = trim((string) $value);

        return self::isValid($value) ? $value : $fallback;
    }

    public static function isValid(string $value): bool
    {
        return preg_match(self::PATTERN, trim($value)) === 1;
    }
}
