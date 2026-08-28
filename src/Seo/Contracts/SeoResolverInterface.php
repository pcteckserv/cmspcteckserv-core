<?php

namespace Pcteckserv\CmsCore\Seo\Contracts;

interface SeoResolverInterface
{
    public function supports(mixed $subject): bool;

    public function resolve(mixed $subject): array;
}
