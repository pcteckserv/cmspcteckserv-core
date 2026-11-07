<?php

namespace Pcteckserv\CmsCore\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class EditCountryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('core.geography.update') === true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('_regions_present') === '1' && !$this->exists('regions')) {
            $this->merge(['regions' => []]);
        }
        if (is_string($this->input('new_region')) && trim($this->input('new_region')) !== '' && is_array($this->input('regions'))) {
            $this->merge(['regions' => [...$this->input('regions'), ['code' => '', 'name' => trim($this->input('new_region'))]]]);
        }
    }

    public function rules(): array
    {
        return [
            '_revision' => ['required', 'integer', 'min:0'],
            'name' => ['required', 'string', 'max:100', 'regex:/^[^\x00-\x1F\x7F<>]+$/u'],
            'new_region' => ['nullable', 'string', 'max:80', 'regex:/^[^\x00-\x1F\x7F<>]+$/u'],
            'regions' => ['present', 'array', 'max:1000'], 'regions.*' => ['array:code,name'],
            'regions.*.code' => ['nullable', 'string', 'max:80', 'distinct'],
            'regions.*.name' => ['required', 'string', 'max:80', 'regex:/^[^\x00-\x1F\x7F<>]+$/u'],
        ];
    }

    public function messages(): array
    {
        return ['required' => 'Preencha :attribute.', 'present' => 'Preserve a lista de regiões.', 'string' => 'Indique texto válido em :attribute.', 'max' => 'O campo :attribute excede o limite.', 'regex' => 'O campo :attribute contém caracteres inválidos.', 'distinct' => 'Não repita regiões.', 'array' => 'Reveja os dados das regiões.', 'integer' => 'Recarregue a página antes de guardar.', 'min' => 'Recarregue a página antes de guardar.'];
    }

    public function safeInput(): array
    {
        $regions = is_array($this->input('regions')) ? array_slice($this->input('regions'), 0, 1000, true) : [];

        return [
            '_revision' => is_scalar($this->input('_revision')) ? $this->input('_revision') : '',
            'name' => is_string($this->input('name')) ? mb_substr($this->input('name'), 0, 100) : '',
            'regions' => array_map(static fn ($row) => is_array($row) ? array_map(static fn ($value) => is_string($value) ? mb_substr($value, 0, 80) : '', array_intersect_key($row, array_flip(['code', 'name']))) : [], $regions),
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
