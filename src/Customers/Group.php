<?php

namespace Pcteckserv\CmsCore\Customers;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $table = 'cms_customer_groups';

    protected $guarded = ['id'];

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'cms_customer_group_members', 'group_id', 'customer_id');
    }
}
