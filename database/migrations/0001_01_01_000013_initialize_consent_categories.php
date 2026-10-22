<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Pcteckserv\CmsCore\Database\Seeders\ConsentManagerSeeder;
use Pcteckserv\CmsCore\Models\ConsentCategory;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            // Uma instalação já configurada pode ter categorias próprias ou desativadas.
            if (ConsentCategory::query()->exists()) {
                return;
            }

            app(ConsentManagerSeeder::class)->seedCategories();
        });
    }

    public function down(): void
    {
        // Preserva categorias que podem já ter serviços associados e edições administrativas.
    }
};
