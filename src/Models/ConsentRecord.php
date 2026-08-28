<?php

namespace Pcteckserv\CmsCore\Models;

use Illuminate\Database\Eloquent\Model;

class ConsentRecord extends Model
{
    protected $table = 'cms_consent_records';

    protected $fillable = ['anonymous_uuid', 'consent_version', 'categories_json'];

    protected $casts = ['categories_json' => 'array'];
}
