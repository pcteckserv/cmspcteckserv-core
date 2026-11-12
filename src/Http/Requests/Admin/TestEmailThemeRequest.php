<?php

namespace Pcteckserv\CmsCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class TestEmailThemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('core.email-theme.test') === true;
    }

    public function rules(): array
    {
        return ['recipient' => ['required', 'string', 'email:rfc', 'max:254', 'regex:/^[^\x00-\x20\x7F]+$/u']];
    }

    public function messages(): array
    {
        return [
            'required' => 'Indique o destinatário do email de teste.',
            'string' => 'Indique um email válido.',
            'email' => 'Indique um email válido.',
            'max' => 'O email não pode exceder 254 caracteres.',
            'regex' => 'O email não pode conter espaços ou caracteres de controlo.',
        ];
    }
}
