<?php

namespace Pcteckserv\CmsCore\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Pcteckserv\CmsCore\Support\IconCatalog;

class CmsIconPicker extends Component
{
    public readonly array $options;

    public function __construct(
        public readonly string $name,
        public readonly ?string $id = null,
        public readonly ?string $label = null,
        public readonly ?string $value = null,
        public readonly string $emptyLabel = 'Escolher ícone',
    ) {
        $this->options = IconCatalog::options();
    }

    public function inputId(): string
    {
        return $this->id ?: str_replace(['[', ']'], ['_', ''], $this->name);
    }

    public function selectedIcon(?string $value = null): string
    {
        $value ??= $this->value;

        return array_key_exists((string) $value, $this->options) ? (string) $value : '';
    }

    public function render(): View
    {
        return view('cms-core::components.icon-picker');
    }
}
