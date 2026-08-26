<?php

namespace Pcteckserv\CmsCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MediaCollection extends Model
{
    protected $table = 'cms_media_collections';

    protected $fillable = ['uuid', 'name', 'slug'];

    protected static function booted(): void
    {
        static::creating(function (MediaCollection $collection): void {
            $collection->uuid ??= (string) Str::uuid();
            $collection->slug ??= Str::slug($collection->name);
        });
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'collection_id');
    }
}
