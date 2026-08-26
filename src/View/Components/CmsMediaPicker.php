<?php

namespace Pcteckserv\CmsCore\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CmsMediaPicker extends Component
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $id = null,
        public readonly ?string $label = null,
        public readonly mixed $value = null,
        public readonly string $type = 'image',
        public readonly string $buttonLabel = 'Escolher',
        public readonly string $emptyLabel = 'Sem imagem selecionada',
        public readonly string $help = 'Selecione ou carregue uma imagem do Media Manager.',
    ) {
    }

    public function inputId(): string
    {
        return $this->id ?: str_replace(['[', ']'], ['_', ''], $this->name);
    }

    public function render(): View
    {
        return view('cms-core::components.media-picker');
    }
}
