<?php

namespace Pcteckserv\CmsCore\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Media extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pendente';
    public const STATUS_PROCESSING = 'a_processar';
    public const STATUS_OPTIMIZED = 'otimizado';
    public const STATUS_FAILED = 'falhou';

    protected $table = 'cms_media';

    protected $fillable = [
        'uuid', 'collection_id', 'disk', 'directory', 'filename', 'path', 'optimized_path',
        'thumbnail_path', 'original_filename', 'extension', 'mime_type', 'media_type',
        'size', 'width', 'height', 'checksum', 'alt_text', 'title', 'caption',
        'description', 'uploaded_by', 'optimization_status', 'original_size',
        'optimized_size', 'variants', 'metadata',
    ];

    protected $casts = [
        'variants' => 'array',
        'metadata' => 'array',
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'original_size' => 'integer',
        'optimized_size' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Media $media): void {
            $media->uuid ??= (string) Str::uuid();
        });
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(MediaCollection::class, 'collection_id');
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search): void {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('filename', 'like', "%{$search}%")
                    ->orWhere('original_filename', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('alt_text', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        });
    }

    public function scopeType(Builder $query, ?string $type): Builder
    {
        return $query->when($type, fn (Builder $query) => $query->where('media_type', $type));
    }

    public function mediables(string $class): MorphToMany
    {
        return $this->morphedByMany($class, 'mediable', 'cms_mediables')->withTimestamps();
    }
}
