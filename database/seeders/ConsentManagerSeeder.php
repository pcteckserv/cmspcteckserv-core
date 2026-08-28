<?php

namespace Pcteckserv\CmsCore\Database\Seeders;

use Illuminate\Database\Seeder;
use Pcteckserv\CmsCore\Consent\ConsentManager;
use Pcteckserv\CmsCore\Models\ConsentCategory;

class ConsentManagerSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['key' => 'necessary', 'name' => 'Necessários', 'description' => 'Essenciais para segurança e funcionamento do website.', 'sort_order' => 10, 'is_required' => true],
            ['key' => 'preferences', 'name' => 'Preferências', 'description' => 'Guardam escolhas como idioma, tema ou configurações personalizadas.', 'sort_order' => 20, 'is_required' => false],
            ['key' => 'analytics', 'name' => 'Estatística', 'description' => 'Ajudam a medir a utilização do website e a melhorar conteúdos.', 'sort_order' => 30, 'is_required' => false],
            ['key' => 'marketing', 'name' => 'Marketing', 'description' => 'Permitem publicidade, remarketing, pixels e conteúdos externos personalizados.', 'sort_order' => 40, 'is_required' => false],
        ] as $category) {
            ConsentCategory::query()->firstOrCreate(['key' => $category['key']], $category + ['is_active' => true]);
        }

        app(ConsentManager::class)->settings();
        app(ConsentManager::class)->publish(false);
    }
}
