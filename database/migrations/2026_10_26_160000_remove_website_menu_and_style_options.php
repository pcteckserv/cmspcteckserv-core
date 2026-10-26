<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cms_site_options')) {
            DB::table('cms_site_options')->whereIn('key', ['website.menus', 'website.css'])->delete();
        }
    }

    public function down(): void
    {
        // Removed theme settings cannot be reconstructed. Shared site options stay intact.
    }
};
