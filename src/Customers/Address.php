<?php

namespace Pcteckserv\CmsCore\Customers;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $table = 'cms_shop_addresses';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['details' => 'array'];
    }
}
