<?php

namespace Pcteckserv\CmsCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('media.upload') ?? false;
    }

    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'max:20'],
            'files.*' => ['required', 'file', 'max:'.(int) config('cms-core.media.max_size', 10240)],
            'collection_id' => ['nullable', 'integer', 'exists:cms_media_collections,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'files.required' => 'Selecione pelo menos um ficheiro.',
            'files.*.file' => 'O envio contém um ficheiro inválido.',
            'files.*.max' => 'O ficheiro excede o tamanho máximo permitido.',
            'collection_id.exists' => 'A coleção selecionada não existe.',
        ];
    }
}
