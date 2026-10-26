<?php

namespace Pcteckserv\CmsCore\Support\Navigation;

class MenuLinkRegistry
{
    private array $sources = [];

    public function register(string $key, string $label, callable $links): void
    {
        $this->sources[$key] = ['label' => $label, 'links' => $links];
    }

    public function groups(): array
    {
        return collect($this->sources)->map(fn ($source) => [
            'label' => $source['label'], 'links' => ($source['links'])(),
        ])->filter(fn ($source) => count($source['links']) > 0)->all();
    }
}
