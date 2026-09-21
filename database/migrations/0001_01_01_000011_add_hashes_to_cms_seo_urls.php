<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('seo_redirects', 'source_hash')) {
            Schema::table('seo_redirects', function (Blueprint $table): void {
                $table->string('source_hash', 64)->nullable()->after('source');
            });

            DB::table('seo_redirects')->orderBy('id')->eachById(function (object $redirect): void {
                DB::table('seo_redirects')->where('id', $redirect->id)->update([
                    'source_hash' => hash('sha256', $redirect->source),
                ]);
            });

            Schema::table('seo_redirects', function (Blueprint $table): void {
                $table->unique('source_hash');
            });
        }

        if (! Schema::hasColumn('seo_not_found_errors', 'url_hash')) {
            Schema::table('seo_not_found_errors', function (Blueprint $table): void {
                $table->string('url_hash', 64)->nullable()->after('url');
            });

            DB::table('seo_not_found_errors')->orderBy('id')->eachById(function (object $error): void {
                DB::table('seo_not_found_errors')->where('id', $error->id)->update([
                    'url_hash' => hash('sha256', $error->url),
                ]);
            });

            Schema::table('seo_not_found_errors', function (Blueprint $table): void {
                $table->unique(['url_hash', 'method']);
            });
        }

        if (! Schema::hasColumn('seo_audits', 'url_hash')) {
            Schema::table('seo_audits', function (Blueprint $table): void {
                $table->string('url_hash', 64)->nullable()->after('url')->index();
            });

            DB::table('seo_audits')->orderBy('id')->eachById(function (object $audit): void {
                DB::table('seo_audits')->where('id', $audit->id)->update([
                    'url_hash' => hash('sha256', $audit->url),
                ]);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('seo_audits', 'url_hash')) {
            Schema::table('seo_audits', fn (Blueprint $table) => $table->dropColumn('url_hash'));
        }

        if (Schema::hasColumn('seo_not_found_errors', 'url_hash')) {
            Schema::table('seo_not_found_errors', fn (Blueprint $table) => $table->dropColumn('url_hash'));
        }

        if (Schema::hasColumn('seo_redirects', 'source_hash')) {
            Schema::table('seo_redirects', fn (Blueprint $table) => $table->dropColumn('source_hash'));
        }
    }
};
