<?php

namespace Pcteckserv\CmsCore\Http\Requests\Admin\Consent;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConsentCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('consent.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'public_text' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_required' => ['nullable', 'boolean'],
            'color' => ['nullable', 'string', 'max:50'],
            'icon' => ['nullable', 'string', 'max:80'],
        ];
    }
}
