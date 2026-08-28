<?php

namespace Pcteckserv\CmsCore\Consent;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Pcteckserv\CmsCore\ActivityLog\Contracts\ActivityLoggerContract;
use Pcteckserv\CmsCore\Consent\Contracts\ConsentManagerContract;
use Pcteckserv\CmsCore\Consent\Knowledge\ConsentKnowledgeBase;
use Pcteckserv\CmsCore\Models\ConsentCategory;
use Pcteckserv\CmsCore\Models\ConsentService;
use Pcteckserv\CmsCore\Models\ConsentSetting;
use Pcteckserv\CmsCore\Models\ConsentTechnology;

class ConsentManager implements ConsentManagerContract
{
    public const CACHE_KEY = 'cms_consent_published_config';

    private array $scripts = [];

    public function __construct(
        private readonly ConsentKnowledgeBase $knowledgeBase,
        private readonly ActivityLoggerContract $activityLogger,
    ) {
    }

    public function registerService(array $definition): void
    {
        $category = isset($definition['category'])
            ? ConsentCategory::query()->where('key', $definition['category'])->first()
            : null;

        $service = ConsentService::query()->updateOrCreate(
            ['key' => $definition['key']],
            [
                'category_id' => $category?->id,
                'name' => $definition['name'],
                'provider' => $definition['provider'] ?? null,
                'description' => $definition['description'] ?? null,
                'purpose' => $definition['purpose'] ?? null,
                'status' => $definition['status'] ?? 'active',
                'requires_consent' => $definition['requires_consent'] ?? ! ($category?->is_required ?? false),
                'source' => $definition['source'] ?? 'plugin',
                'confidence' => $definition['confidence'] ?? 100,
                'review_status' => $definition['review_status'] ?? 'suggested',
            ],
        );

        foreach (['cookies' => 'cookie', 'domains' => 'domain', 'storage' => 'storage', 'iframes' => 'iframe'] as $key => $type) {
            foreach ($definition[$key] ?? [] as $item) {
                ConsentTechnology::query()->firstOrCreate([
                    'service_id' => $service->id,
                    'type' => $type,
                    'name' => (string) $item,
                ]);
            }
        }

        $this->forgetCache();
    }

    public function registerScript(array $definition): void
    {
        $this->scripts[$definition['key']] = $definition;
    }

    public function registeredScripts(): array
    {
        return $this->scripts;
    }

    public function registerKnowledgeSignature(array $signature): void
    {
        $this->knowledgeBase->register($signature);
    }

    public function publishedConfig(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $settings = $this->settings();

            if (is_array($settings->published_config)) {
                return $settings->published_config;
            }

            return $this->buildConfig($settings);
        });
    }

    public function publish(bool $incrementVersion = false): array
    {
        return DB::transaction(function () use ($incrementVersion): array {
            $settings = $this->settings();
            $oldVersion = $settings->version;

            if ($incrementVersion) {
                $settings->version++;
            }

            $config = $this->buildConfig($settings);
            $settings->forceFill([
                'published_config' => $config,
                'published_at' => now(),
            ])->save();

            $this->forgetCache();
            $this->activityLogger->log('consent.publish', 'Consentimentos', 'Configuração de consentimentos publicada.', $settings, ['increment_version' => $incrementVersion], ['version' => $oldVersion], ['version' => $settings->version]);

            return $config;
        });
    }

    public function settings(): ConsentSetting
    {
        return ConsentSetting::query()->firstOrCreate([], [
            'version' => 1,
            'banner_enabled' => true,
            'server_records_enabled' => false,
            'texts' => $this->defaultTexts(),
        ]);
    }

    public function defaultTexts(): array
    {
        return [
            'banner_title' => 'Utilização de cookies',
            'banner_description' => 'Utilizamos cookies e tecnologias semelhantes para garantir o funcionamento do site e, com o seu consentimento, melhorar a experiência e medir resultados.',
            'accept_all' => 'Aceitar todos',
            'reject_optional' => 'Rejeitar não essenciais',
            'customize' => 'Personalizar',
            'save' => 'Guardar preferências',
            'preferences_title' => 'Preferências de cookies',
            'preferences_description' => 'Pode escolher as categorias opcionais que autoriza. Os cookies necessários estão sempre ativos.',
            'configure_link' => 'Configurar cookies',
            'policy_text' => 'Pode alterar a sua decisão a qualquer momento.',
        ];
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function buildConfig(ConsentSetting $settings): array
    {
        $categories = ConsentCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (ConsentCategory $category): array => [
                'key' => $category->key,
                'name' => $category->name,
                'description' => $category->public_text ?: $category->description,
                'required' => $category->is_required,
                'color' => $category->color,
                'icon' => $category->icon,
            ])
            ->values()
            ->all();

        $services = ConsentService::query()
            ->with('category')
            ->where('status', 'active')
            ->get()
            ->map(fn (ConsentService $service): array => [
                'key' => $service->key,
                'name' => $service->name,
                'provider' => $service->provider,
                'category' => $service->category?->key,
                'requires_consent' => $service->requires_consent,
                'review_status' => $service->review_status,
            ])
            ->values()
            ->all();

        return [
            'version' => $settings->version,
            'enabled' => $settings->banner_enabled,
            'texts' => array_replace($this->defaultTexts(), $settings->texts ?? []),
            'categories' => $categories,
            'services' => $services,
            'scripts' => array_values($this->scripts),
        ];
    }
}
