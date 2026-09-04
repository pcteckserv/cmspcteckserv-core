<?php

namespace Pcteckserv\CmsCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InstallPluginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('plugins.install') ?? false;
    }

    public function rules(): array
    {
        return [
            'package' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9_.-]+\/[a-z0-9_.-]+$/'],
            'version_constraint' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9*.^~<>=|, _.-]+(@dev|@alpha|@beta|@RC|@stable)?$/'],
            'slug' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9][a-z0-9_-]*$/'],
            'label' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'provider' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z_][A-Za-z0-9_\\\\]*$/'],
            'repository_type' => ['nullable', Rule::in(['path', 'vcs'])],
            'repository_url' => ['nullable', 'string', 'max:500', 'required_with:repository_type'],
        ];
    }

    public function messages(): array
    {
        return [
            'package.required' => 'Indique a package Composer do plugin.',
            'package.regex' => 'A package deve usar o formato vendor/package.',
            'version_constraint.regex' => 'A constraint de versão indicada não é válida.',
            'slug.regex' => 'O identificador só pode conter letras minúsculas, números, hífenes e underscores.',
            'provider.regex' => 'O service provider indicado não é válido.',
            'repository_type.in' => 'O tipo de repositório selecionado não é válido.',
            'repository_url.required_with' => 'Indique o caminho ou URL do repositório.',
        ];
    }
}
