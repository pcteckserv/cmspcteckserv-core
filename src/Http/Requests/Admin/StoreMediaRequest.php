<?php

namespace Pcteckserv\CmsCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
            'files.*' => ['required', 'file', 'max:'.(int) config('cms-core.media.max_size', 24576)],
            'collection_id' => ['nullable', 'integer', 'exists:cms_media_collections,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'files.required' => 'Selecione pelo menos um ficheiro.',
            'files.*.file' => 'O envio contém um ficheiro inválido.',
            'files.*.uploaded' => 'Não foi possível receber o ficheiro. Verifique o limite de upload do servidor, o espaço temporário e tente novamente com uma imagem mais pequena.',
            'files.*.max' => 'O ficheiro excede o limite de '.round(config('cms-core.media.max_size', 24576) / 1024).' MB.',
            'collection_id.exists' => 'A coleção selecionada não existe.',
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            $bytes = collect($this->file('files', []))->filter(fn ($file) => $file instanceof UploadedFile && $file->isValid())->sum(fn ($file) => $file->getSize());
            if ($bytes > (int) config('cms-core.media.batch_max_size', 245760) * 1024) {
                $validator->errors()->add('files', 'O envio excede 240 MB no total. Divida os ficheiros por vários envios.');
            }
        }];
    }
}
