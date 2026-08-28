<?php

namespace Pcteckserv\CmsCore\Http\Requests\Admin\Consent;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConsentSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('consent.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'banner_enabled' => ['nullable', 'boolean'],
            'server_records_enabled' => ['nullable', 'boolean'],
            'texts' => ['required', 'array'],
            'texts.*' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
