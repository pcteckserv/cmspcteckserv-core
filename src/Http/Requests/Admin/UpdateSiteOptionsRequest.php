<?php

namespace Pcteckserv\CmsCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Pcteckserv\CmsCore\Rules\SiteLink;

class UpdateSiteOptionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('core.site-options.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'site_title' => ['required', 'string', 'max:120'],
            'site_description' => ['nullable', 'string', 'max:255'],
            'site_icon_url' => ['nullable', 'string', new SiteLink, 'max:2048'],
            'site_icon_media_id' => [
                'nullable',
                'integer',
                Rule::exists('cms_media', 'id')->where(
                    fn ($query) => $query->where('media_type', 'image')->whereNull('deleted_at')
                ),
            ],
            'remove_site_icon' => ['nullable', 'boolean'],
            'site_url' => ['exclude'],
            'admin_email' => ['required', 'email', 'max:255'],
            'locale' => ['required', Rule::in(array_keys($this->locales()))],
        ];
    }

    private function locales(): array
    {
        return [
            'pt_PT' => 'Português',
            'en_US' => 'Inglês',
            'es_ES' => 'Espanhol',
            'fr_FR' => 'Francês',
        ];
    }
}
