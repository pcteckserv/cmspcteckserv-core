<?php

namespace Pcteckserv\CmsCore\Services\Footer;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Pcteckserv\CmsCore\Models\Media;
use Pcteckserv\CmsCore\Services\Media\MediaService;
use Pcteckserv\CmsCore\Support\CssLength;
use Pcteckserv\CmsCore\Support\SiteOptions;
use Throwable;

class FooterSettingsService
{
    public function __construct(
        private readonly SiteOptions $siteOptions,
        private readonly MediaService $mediaService,
    ) {
    }

    public function settings(): array
    {
        $options = $this->siteOptions->all();

        return [
            'enabled' => $this->truthy($options['footer_enabled'] ?? true),
            'year' => now()->year,
            'site_title' => (string) ($options['site_title'] ?? config('app.name', 'CMS')),
            'copyright_text' => (string) ($options['footer_copyright_text'] ?? 'Todos os direitos reservados'),
            'background_color' => $this->color($options['footer_background_color'] ?? null, '#0C0C0C'),
            'text_color' => $this->color($options['footer_text_color'] ?? null, '#FFFFFF'),
            'secondary_text_color' => $this->color($options['footer_secondary_text_color'] ?? null, '#FFFFFF'),
            'show_pcteckserv_credit' => $this->truthy($options['footer_show_pcteckserv_credit'] ?? true),
            'credit_text' => (string) ($options['footer_credit_text'] ?? 'Desenvolvido por'),
            'pcteckserv_logo_url' => $this->logoUrl($options),
            'pcteckserv_logo_height' => $this->cssSize($options['footer_pcteckserv_logo_height'] ?? null, '18px'),
            'pcteckserv_logo_max_width' => $this->cssSize($options['footer_pcteckserv_logo_max_width'] ?? null, '140px'),
            'pcteckserv_url' => $this->url($options['footer_pcteckserv_url'] ?? null),
            'padding_y' => $this->cssSize($options['footer_padding_y'] ?? null, '28px'),
            'padding_x' => $this->cssSize($options['footer_padding_x'] ?? null, '24px'),
            'max_width' => $this->cssSize($options['footer_max_width'] ?? null, '1320px'),
        ];
    }

    private function logoUrl(array $options): ?string
    {
        $mediaId = (int) ($options['footer_pcteckserv_logo_media_id'] ?? 0);

        if ($mediaId > 0 && $media = $this->media($mediaId)) {
            return $this->mediaService->url($media);
        }

        $path = trim((string) ($options['footer_pcteckserv_logo_path'] ?? ''), '/');

        if ($path !== '' && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        if ($path !== '' && file_exists(public_path($path))) {
            return asset($path);
        }

        return null;
    }

    private function media(int $id): ?Media
    {
        try {
            if (! Schema::hasTable('cms_media')) {
                return null;
            }
        } catch (Throwable) {
            return null;
        }

        return Media::query()
            ->where('media_type', 'image')
            ->find($id);
    }

    private function truthy(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function color(mixed $value, string $fallback): string
    {
        $value = (string) $value;

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1 ? $value : $fallback;
    }

    private function cssSize(mixed $value, string $fallback): string
    {
        return CssLength::normalize($value, $fallback);
    }

    private function url(mixed $value): string
    {
        $value = (string) $value;
        $scheme = parse_url($value, PHP_URL_SCHEME);

        if (filter_var($value, FILTER_VALIDATE_URL) && in_array($scheme, ['http', 'https'], true)) {
            return $value;
        }

        return 'https://pcteckserv.com';
    }

}
