<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_geography', function (Blueprint $table): void {
            $table->char('country', 2)->primary();
            $table->string('name', 100);
            $table->json('regions');
            $table->string('source', 100);
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
        });

        if (Schema::hasTable('cms_shop_geography')) {
            DB::transaction(function (): void {
                foreach (DB::table('cms_shop_geography')->orderBy('country')->get() as $row) {
                    DB::table('cms_geography')->insertOrIgnore((array) $row);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cms_shop_geography')) {
            DB::transaction(function (): void {
                foreach (DB::table('cms_geography')->orderBy('country')->get() as $row) {
                    DB::table('cms_shop_geography')->updateOrInsert(['country' => $row->country], (array) $row);
                }
            });
        }
        Schema::dropIfExists('cms_geography');
    }
};
