<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_site_options', function (Blueprint $table) {
            $table->longText('value')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Preserve capacity to avoid truncating saved policy and menu content on rollback.
    }
};
