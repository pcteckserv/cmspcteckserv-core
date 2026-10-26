<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_user_states', fn (Blueprint $table) => $table->boolean('direct_permissions_enabled')->default(false));
        DB::table('cms_permission_user')->select('user_type', 'user_id')->distinct()->orderBy('user_id')->chunk(500, function ($users) {
            $rows = $users->map(fn ($user) => ['user_type' => $user->user_type, 'user_id' => $user->user_id, 'state' => 'active', 'direct_permissions_enabled' => true, 'created_at' => now(), 'updated_at' => now()])->all();
            if ($rows) {
                DB::table('cms_user_states')->upsert($rows, ['user_type', 'user_id'], ['direct_permissions_enabled']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('cms_user_states', fn (Blueprint $table) => $table->dropColumn('direct_permissions_enabled'));
    }
};
