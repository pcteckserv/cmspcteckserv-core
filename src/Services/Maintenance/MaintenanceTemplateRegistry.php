<?php

namespace Pcteckserv\CmsCore\Services\Maintenance;

use InvalidArgumentException;

class MaintenanceTemplateRegistry
{
    /** @var array<string, MaintenanceTemplate> */
    private array $templates = [];

    public function __construct()
    {
        $this->register(new MaintenanceTemplate('minimal', 'Minimalista', 'Layout limpo com foco em mensagem e acesso privado.', 'cms-core::public.maintenance.minimal'));
        $this->register(new MaintenanceTemplate('modern', 'Moderno', 'Página escura com detalhe tecnológico e contador.', 'cms-core::public.maintenance.modern'));
        $this->register(new MaintenanceTemplate('hero', 'Hero com imagem', 'Imagem em destaque com conteúdo lateral responsivo.', 'cms-core::public.maintenance.hero'));
    }

    public function register(MaintenanceTemplate $template): void
    {
        if (! preg_match('/^[a-z0-9_-]+$/', $template->key)) {
            throw new InvalidArgumentException('A chave do template de manutenção é inválida.');
        }

        $this->templates[$template->key] = $template;
    }

    /** @return array<string, MaintenanceTemplate> */
    public function all(): array
    {
        return $this->templates;
    }

    public function get(string $key): MaintenanceTemplate
    {
        return $this->templates[$key] ?? $this->templates['minimal'];
    }

    /** @return array<int, string> */
    public function keys(): array
    {
        return array_keys($this->templates);
    }
}
