<?php

namespace Pcteckserv\CmsCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique($user::class, 'email')->ignore($user)],
            'current_password' => [
                Rule::requiredIf(fn (): bool => $this->input('email') !== $user->email || $this->filled('password')),
                'nullable', 'string', 'current_password',
            ],
            'password' => ['nullable', 'string', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Indique o seu nome.',
            'name.string' => 'O nome deve ser um texto válido.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'email.required' => 'Indique o seu email.',
            'email.string' => 'Indique um email válido.',
            'email.email' => 'Indique um email válido.',
            'email.max' => 'O email não pode ter mais de 255 caracteres.',
            'email.unique' => 'Este email já está associado a outra conta.',
            'current_password.required' => 'Indique a palavra-passe atual para alterar o email ou a palavra-passe.',
            'current_password.string' => 'Indique uma palavra-passe atual válida.',
            'current_password.current_password' => 'A palavra-passe atual está incorreta.',
            'password.string' => 'Indique uma nova palavra-passe válida.',
            'password.confirmed' => 'A confirmação da nova palavra-passe não coincide.',
            'password.min' => 'A nova palavra-passe deve ter pelo menos :min caracteres.',
            'password.max' => 'A nova palavra-passe não pode ter mais de :max caracteres.',
            'password.letters' => 'A nova palavra-passe deve conter pelo menos uma letra.',
            'password.mixed' => 'A nova palavra-passe deve conter letras maiúsculas e minúsculas.',
            'password.numbers' => 'A nova palavra-passe deve conter pelo menos um número.',
            'password.symbols' => 'A nova palavra-passe deve conter pelo menos um símbolo.',
            'password.uncompromised' => 'Esta palavra-passe foi exposta numa fuga de dados. Escolha outra.',
        ];
    }
}
