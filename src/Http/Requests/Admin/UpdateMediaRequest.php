<?php

namespace Pcteckserv\CmsCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('media.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:3000'],
            'collection_id' => ['nullable', 'integer', 'exists:cms_media_collections,id'],
        ];
    }
}
