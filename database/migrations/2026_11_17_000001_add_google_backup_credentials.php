<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_backup_destinations', function (Blueprint $table): void {
            $table->text('google_client_id')->nullable();
            $table->text('google_client_secret')->nullable();
            $table->text('google_tokens')->nullable();
            $table->string('google_folder_id')->nullable();
        });
        Schema::table('cms_backup_runs', fn (Blueprint $table) => $table->longText('destination_snapshot')->nullable());
    }

    public function down(): void
    {
        Schema::table('cms_backup_runs', fn (Blueprint $table) => $table->dropColumn('destination_snapshot'));
        Schema::table('cms_backup_destinations', fn (Blueprint $table) => $table->dropColumn(['google_client_id', 'google_client_secret', 'google_tokens', 'google_folder_id']));
    }
};
