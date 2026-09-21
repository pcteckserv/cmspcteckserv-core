<?php

namespace Pcteckserv\CmsCore\Seo\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Pcteckserv\CmsCore\Seo\Models\SeoRedirect;

class UpdateSeoRedirectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('seo.redirects.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'source' => [
                'required',
                'string',
                'max:2048',
                'starts_with:/',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $source = '/'.trim((string) $value, '/');
                    $redirect = $this->route('redirect');
                    $redirectId = $redirect instanceof SeoRedirect ? $redirect->getKey() : $redirect;
                    $exists = SeoRedirect::query()
                        ->where('source_hash', hash('sha256', $source))
                        ->when($redirectId, fn ($query) => $query->whereKeyNot($redirectId))
                        ->exists();

                    if ($exists) {
                        $fail('Este URL de origem já está a ser utilizado.');
                    }
                },
            ],
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
