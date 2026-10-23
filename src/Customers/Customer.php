<?php

namespace Pcteckserv\CmsCore\Customers;

use Illuminate\Foundation\Auth\User;
use Illuminate\Notifications\Notifiable;
use Pcteckserv\CmsCore\Contracts\CmsAccessUser;
use Pcteckserv\CmsCore\Models\Concerns\HasCmsAccess;
use Pcteckserv\CmsCore\Models\Role;

class Customer extends User implements CmsAccessUser
{
    use HasCmsAccess;
    use Notifiable;

    public function getMorphClass(): string
    {
        return self::class;
    }

    public function isCmsSuperAdmin(): bool
    {
        return false;
    }

    public function isCmsActive(): bool
    {
        return (bool) $this->active;
    }

    public function hasCmsPermission(string $permission): bool
    {
        return $this->active && $permission === 'core.client.access';
    }

    protected static function booted(): void
    {
        static::created(function ($customer) {
            $role = Role::where('key', 'core.client')->first();
            if ($role) {
                $customer->cmsRoles()->syncWithoutDetaching([$role->id]);
            }
        });
        static::deleted(function ($customer) {
            $customer->cmsRoles()->detach();
            $customer->cmsPermissions()->detach();
            $customer->cmsState()->delete();
        });
    }

    protected $table = 'cms_shop_customers';

    protected $guarded = ['id'];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = ['active' => true];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'active' => 'boolean'];
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'cms_customer_group_members', 'customer_id', 'group_id');
    }

    public function addresses()
    {
        return $this->hasMany(Address::class, 'customer_id');
    }

    public function hasHistory(): bool
    {
        return $this->addresses()->exists() || app(CustomerModules::class)->hasHistory($this);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPassword($token));
    }
}
