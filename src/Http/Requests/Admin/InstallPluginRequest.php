<?php

namespace Pcteckserv\CmsCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class InstallPluginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('plugins.install') ?? false;
    }

    public function rules(): array
    {
        return [
            'plugin' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9][a-z0-9_-]*$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'plugin.required' => 'Selecione o plugin que pretende instalar.',
            'plugin.regex' => 'O identificador do plugin selecionado não é válido.',
        ];
    }
}
