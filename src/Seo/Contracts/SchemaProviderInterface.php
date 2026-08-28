<?php

namespace Pcteckserv\CmsCore\Seo\Contracts;

interface SchemaProviderInterface
{
    public function supports(mixed $subject): bool;

    public function schema(mixed $subject, array $context = []): array;
}
