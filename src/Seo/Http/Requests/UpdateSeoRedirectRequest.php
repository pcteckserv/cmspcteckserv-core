<?php

namespace Pcteckserv\CmsCore\Seo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSeoRedirectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('seo.redirects.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'source' => ['required', 'string', 'max:2048', 'starts_with:/', Rule::unique('seo_redirects', 'source')->ignore($this->route('redirect'))],
            'destination' => ['required', 'string', 'max:2048', 'different:source', 'not_regex:/^\s*(javascript|data):/i'],
            'status_code' => ['required', 'integer', Rule::in([301, 302, 307, 308])],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function validated($key = null, $default = null): mixed
    {
        $validated = parent::validated($key, $default);

        if ($key !== null) {
            return $validated;
        }

        $validated['source'] = '/'.trim($validated['source'], '/');
        $validated['destination'] = str_starts_with($validated['destination'], 'http') ? $validated['destination'] : '/'.trim($validated['destination'], '/');
        $validated['is_active'] = $this->boolean('is_active');

        return $validated;
    }
}
