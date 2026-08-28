<?php

namespace Pcteckserv\CmsCore\Consent\Scanner;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Pcteckserv\CmsCore\Consent\Knowledge\ConsentKnowledgeBase;
use Pcteckserv\CmsCore\Models\ConsentCategory;
use Pcteckserv\CmsCore\Models\ConsentScan;
use Pcteckserv\CmsCore\Models\ConsentScanItem;
use Pcteckserv\CmsCore\Models\ConsentService;
use Pcteckserv\CmsCore\Models\ConsentTechnology;

class ConsentScanner
{
    public function __construct(private readonly ConsentKnowledgeBase $knowledgeBase)
    {
    }

    public function scan(ConsentScan $scan, array $urls): ConsentScan
    {
        $scan->update(['status' => 'running', 'started_at' => now(), 'urls' => $urls]);

        try {
            $found = [];

            foreach ($urls as $url) {
                foreach ($this->scanUrl($url) as $item) {
                    $isNew = ! ConsentTechnology::query()
                        ->where('type', $item['type'])
                        ->where('name', $item['identifier'])
                        ->exists();
                    $found[] = $item + ['page_url' => $url, 'is_new' => $isNew];
                    $service = $this->registerDetectedService($item, $url);

                    ConsentScanItem::query()->create([
                        'scan_id' => $scan->id,
                        'service_id' => $service?->id,
                        'type' => $item['type'],
                        'identifier' => $item['identifier'],
                        'domain' => $item['domain'] ?? null,
                        'url' => $url,
                        'metadata' => $item,
                    ]);
                }
            }

            $changes = collect($found)->where('is_new', true)->count();
            $scan->update([
                'status' => 'completed',
                'finished_at' => now(),
                'pages_scanned' => count($urls),
                'services_found' => ConsentService::query()->count(),
                'technologies_found' => count($found),
                'changes_found' => $changes,
                'summary' => ['new_or_unknown' => $changes],
            ]);
        } catch (\Throwable $exception) {
            $scan->update(['status' => 'failed', 'finished_at' => now(), 'error_log' => $exception->getMessage()]);
        }

        return $scan->refresh();
    }

    private function scanUrl(string $url): array
    {
        $absoluteUrl = url($url);
        $response = Http::timeout(12)->get($absoluteUrl);

        if (! $response->successful()) {
            return [];
        }

        $html = $response->body();
        $items = [];

        preg_match_all('/<script[^>]+src=["\']([^"\']+)["\']/i', $html, $scripts);
        foreach ($scripts[1] ?? [] as $script) {
            $items[] = ['type' => 'script', 'identifier' => $script, 'domain' => parse_url($script, PHP_URL_HOST)];
        }

        preg_match_all('/<iframe[^>]+src=["\']([^"\']+)["\']/i', $html, $iframes);
        foreach ($iframes[1] ?? [] as $iframe) {
            $items[] = ['type' => 'iframe', 'identifier' => $iframe, 'domain' => parse_url($iframe, PHP_URL_HOST)];
        }

        preg_match_all('/localStorage\.setItem\(["\']([^"\']+)["\']/i', $html, $localStorage);
        foreach ($localStorage[1] ?? [] as $key) {
            $items[] = ['type' => 'local_storage', 'identifier' => $key];
        }

        preg_match_all('/sessionStorage\.setItem\(["\']([^"\']+)["\']/i', $html, $sessionStorage);
        foreach ($sessionStorage[1] ?? [] as $key) {
            $items[] = ['type' => 'session_storage', 'identifier' => $key];
        }

        return collect($items)->unique(fn (array $item): string => $item['type'].'|'.$item['identifier'])->values()->all();
    }

    private function registerDetectedService(array $item, string $url): ?ConsentService
    {
        $signature = $this->knowledgeBase->match($item['identifier'].' '.($item['domain'] ?? ''));
        $category = $signature ? ConsentCategory::query()->where('key', $signature['category'])->first() : null;

        $key = $signature['service_key'] ?? 'unknown-'.Str::slug($item['domain'] ?: $item['identifier']);
        $service = ConsentService::query()->firstOrCreate(
            ['key' => $key],
            [
                'category_id' => $category?->id,
                'name' => $signature['service_name'] ?? 'Tecnologia desconhecida',
                'provider' => $signature['provider'] ?? $item['domain'] ?? null,
                'status' => 'active',
                'requires_consent' => ! ($category?->is_required ?? false),
                'source' => 'scanner',
                'confidence' => $signature['confidence'] ?? 25,
                'review_status' => $signature ? 'suggested' : 'requires_review',
                'detection_reason' => $signature['reason'] ?? 'Tecnologia não reconhecida pela base de conhecimento.',
                'found_on_urls' => [$url],
            ],
        );

        $urls = collect($service->found_on_urls ?? [])->push($url)->unique()->values()->all();
        $service->forceFill(['found_on_urls' => $urls])->save();

        ConsentTechnology::query()->firstOrCreate([
            'service_id' => $service->id,
            'type' => $item['type'],
            'name' => $item['identifier'],
        ], [
            'domain' => $item['domain'] ?? null,
            'found_on_urls' => [$url],
            'is_third_party' => isset($item['domain']) && $item['domain'] !== parse_url(config('app.url'), PHP_URL_HOST),
        ]);

        return $service;
    }

}
