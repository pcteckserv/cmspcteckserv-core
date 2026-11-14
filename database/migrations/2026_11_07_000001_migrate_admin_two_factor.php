<?php

use Illuminate\Database\Migrations\Migration;
use Pcteckserv\CmsCore\Services\Auth\LegacyTwoFactorMigration;

return new class extends Migration
{
    public function up(): void
    {
        $model = config('cms-core.user_model') ?: config('auth.providers.users.model');
        app(LegacyTwoFactorMigration::class)->migrate(new $model, 'web');
    }

    public function down(): void
    {
        // Os fatores importados e a tabela antiga são preservados num rollback.
    }
};
