<?php

namespace Pcteckserv\CmsCore\Consent\Knowledge;

class ConsentKnowledgeBase
{
    private array $signatures = [];

    public function __construct()
    {
        $this->registerMany([
            ['service_key' => 'google-analytics', 'service_name' => 'Google Analytics', 'provider' => 'Google', 'category' => 'analytics', 'confidence' => 99, 'patterns' => ['_ga', '_gid', 'google-analytics.com', 'googletagmanager.com'], 'reason' => 'Assinatura conhecida de Google Analytics.'],
            ['service_key' => 'meta-pixel', 'service_name' => 'Meta Pixel', 'provider' => 'Meta', 'category' => 'marketing', 'confidence' => 98, 'patterns' => ['_fbp', 'connect.facebook.net', 'facebook.com/tr'], 'reason' => 'Assinatura conhecida de Meta Pixel.'],
            ['service_key' => 'microsoft-clarity', 'service_name' => 'Microsoft Clarity', 'provider' => 'Microsoft', 'category' => 'analytics', 'confidence' => 97, 'patterns' => ['_clck', '_clsk', 'clarity.ms'], 'reason' => 'Assinatura conhecida de Microsoft Clarity.'],
            ['service_key' => 'hotjar', 'service_name' => 'Hotjar', 'provider' => 'Hotjar', 'category' => 'analytics', 'confidence' => 97, 'patterns' => ['_hj', 'hotjar.com'], 'reason' => 'Assinatura conhecida de Hotjar.'],
            ['service_key' => 'youtube', 'service_name' => 'YouTube', 'provider' => 'Google', 'category' => 'marketing', 'confidence' => 90, 'patterns' => ['youtube.com', 'youtu.be', 'youtube-nocookie.com'], 'reason' => 'Iframe ou recurso externo de YouTube.'],
            ['service_key' => 'vimeo', 'service_name' => 'Vimeo', 'provider' => 'Vimeo', 'category' => 'marketing', 'confidence' => 90, 'patterns' => ['vimeo.com', 'player.vimeo.com'], 'reason' => 'Iframe ou recurso externo de Vimeo.'],
        ]);
    }

    public function register(array $signature): void
    {
        $this->signatures[] = $signature;
    }

    public function registerMany(array $signatures): void
    {
        foreach ($signatures as $signature) {
            $this->register($signature);
        }
    }

    public function match(string $identifier): ?array
    {
        $identifier = mb_strtolower($identifier);

        foreach ($this->signatures as $signature) {
            foreach ($signature['patterns'] ?? [] as $pattern) {
                $pattern = mb_strtolower((string) $pattern);
                $isWildcard = str_contains($pattern, '*');
                $regex = '/^'.str_replace('\*', '.*', preg_quote($pattern, '/')).'$/i';

                if (($isWildcard && preg_match($regex, $identifier)) || str_contains($identifier, $pattern)) {
                    return $signature;
                }
            }
        }

        return null;
    }

    public function all(): array
    {
        return $this->signatures;
    }
}
