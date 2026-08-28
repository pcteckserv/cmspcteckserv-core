<?php

namespace Pcteckserv\CmsCore\Http\Requests\Admin\Consent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Pcteckserv\CmsCore\Models\ConsentCategory;

class UpdateConsentServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('consent.review') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'provider' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'purpose' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['nullable', Rule::exists((new ConsentCategory())->getTable(), 'id')],
            'status' => ['required', Rule::in(['active', 'inactive', 'ignored'])],
            'requires_consent' => ['nullable', 'boolean'],
            'review_status' => ['required', Rule::in(['confirmed', 'suggested', 'requires_review', 'ignored'])],
        ];
    }
}
