<?php

namespace Pcteckserv\CmsCore\Support;

class IconCatalog
{
    private static ?array $libraries = null;

    private static ?array $options = null;

    public static function libraries(): array
    {
        if (self::$libraries === null) {
            self::$libraries = [];
            foreach (['bootstrap', 'fontawesome'] as $library) {
                self::$libraries[$library] = json_decode(
                    file_get_contents(__DIR__.'/../../resources/icons/'.$library.'.json'),
                    true,
                    512,
                    JSON_THROW_ON_ERROR,
                );
            }
        }

        return self::$libraries;
    }

    public static function icon(string $name): ?array
    {
        $library = explode(':', $name, 2)[0];

        return self::libraries()[$library]['icons'][$name] ?? null;
    }

    public static function options(): array
    {
        if (self::$options !== null) {
            return self::$options;
        }

        // Os identificadores antigos continuam válidos para conteúdo já guardado.
        $options = [
            'check' => 'Confirmação',
            'shield-check' => 'Proteção',
            'clock' => 'Relógio',
            'award' => 'Distinção',
            'hammer' => 'Martelo',
            'house' => 'Casa',
            'ruler' => 'Régua',
            'wrench' => 'Ferramenta',
            'leaf' => 'Folha',
            'star' => 'Estrela',
            'map-pin' => 'Localização',
            'phone' => 'Telefone',
            'calendar' => 'Calendário',
            'heart' => 'Coração',
            'sparkles' => 'Brilho',
        ];

        foreach (self::libraries() as $library) {
            foreach ($library['icons'] as $name => $icon) {
                $options[$name] = $library['label'].' · '.$icon['label'];
            }
        }

        return self::$options = $options;
    }
}
