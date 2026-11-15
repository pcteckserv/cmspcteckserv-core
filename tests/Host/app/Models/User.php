<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Pcteckserv\CmsCore\Contracts\CmsAccessUser;
use Pcteckserv\CmsCore\Models\Concerns\HasCmsAccess;

class User extends Authenticatable implements CmsAccessUser
{
    use HasCmsAccess, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at'=>'datetime', 'password'=>'hashed'];
    }
}
