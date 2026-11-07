<?php

namespace Pcteckserv\CmsCore\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Pcteckserv\CmsCore\Geography\CountryNames;

final class ImportCountryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('core.geography.update') === true;
    }

    public function rules(): array
    {
        return [
            'countries' => ['present', 'array', 'list', 'max:249'],
            'countries.*' => ['required', 'string', 'distinct', Rule::in(array_keys(CountryNames::all()))],
        ];
    }

    public function messages(): array
    {
        return ['required' => 'Selecione um país válido.', 'present' => 'Selecione os países a importar.', 'array' => 'Selecione os países na lista.', 'list' => 'Indique uma lista de países.', 'max' => 'A seleção excede o limite.', 'countries.*.string' => 'Selecione um país válido.', 'countries.*.in' => 'Selecione um país válido.', 'distinct' => 'Não repita países.'];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->exists('countries')) {
            $this->merge(['countries' => []]);
        }
    }

    public function safeInput(): array
    {
        return [
            'countries' => is_array($this->input('countries')) ? array_values(array_slice(array_filter($this->input('countries'), static fn ($value) => is_string($value) && strlen($value) === 2), 0, 249)) : [],
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        if ($this->expectsJson()) {
            parent::failedValidation($validator);
        }
        throw new \Illuminate\Http\Exceptions\HttpResponseException(redirect()->back()->withErrors($validator)->withInput($this->safeInput()));
    }
}
