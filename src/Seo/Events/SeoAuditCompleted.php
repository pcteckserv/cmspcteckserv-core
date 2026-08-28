<?php

namespace Pcteckserv\CmsCore\Seo\Events;

use Pcteckserv\CmsCore\Seo\Models\SeoAudit;

class SeoAuditCompleted
{
    public function __construct(public readonly SeoAudit $audit)
    {
    }
}
