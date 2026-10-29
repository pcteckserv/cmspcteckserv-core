<?php

namespace Pcteckserv\CmsCore\Http\Requests\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class TwoFactorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->routeIs('two-factor.*') || $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => $this->routeIs('admin.profile.two-factor.*')
                ? ['required', 'string', 'current_password'] : ['prohibited'],
            'two_factor_code' => $this->routeIs('admin.profile.two-factor.begin')
                ? ['prohibited'] : ['required', 'string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Indique a palavra-passe atual.',
            'current_password.string' => 'Indique uma palavra-passe válida.',
            'current_password.current_password' => 'A palavra-passe atual está incorreta.',
            'current_password.prohibited' => 'Este campo não é permitido.',
            'two_factor_code.required' => 'Indique o código da aplicação ou um código de recuperação.',
            'two_factor_code.string' => 'Indique um código válido.',
            'two_factor_code.max' => 'Indique um código válido.',
            'two_factor_code.prohibited' => 'Este campo não é permitido.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(back()->withErrors($validator));
    }
}
