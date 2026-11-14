<?php

namespace Pcteckserv\CmsCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class UserProfile extends Model
{
    protected $table = 'cms_user_profiles';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['languages' => 'array'];
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? route('cms-core.staff-avatar.public', basename($this->avatar_path)) : null;
    }
}
