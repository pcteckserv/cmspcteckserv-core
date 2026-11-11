<?php

namespace Pcteckserv\CmsCore\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Pcteckserv\CmsCore\Support\SiteUrl;

final class SiteLink implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return is_string($value) ? app(SiteUrl::class)->absolute($value) : $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return is_string($value) ? app(SiteUrl::class)->relative($value) : $value;
    }
}
