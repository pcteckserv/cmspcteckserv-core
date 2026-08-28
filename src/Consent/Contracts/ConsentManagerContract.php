<?php

namespace Pcteckserv\CmsCore\Consent\Contracts;

interface ConsentManagerContract
{
    public function registerService(array $definition): void;

    public function registerScript(array $definition): void;

    public function registerKnowledgeSignature(array $signature): void;

    public function publishedConfig(): array;

    public function settings(): \Pcteckserv\CmsCore\Models\ConsentSetting;

    public function defaultTexts(): array;

    public function publish(bool $incrementVersion = false): array;

    public function forgetCache(): void;
}
