<?php

namespace Pcteckserv\CmsCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'site_icon_url' => ['nullable', 'string', 'max:2048'],
            'site_icon_file' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,ico', 'max:2048'],
            'remove_site_icon' => ['nullable', 'boolean'],
            'site_url' => ['required', 'url', 'max:2048'],
            'admin_email' => ['required', 'email', 'max:255'],
            'locale' => ['required', Rule::in(array_keys($this->locales()))],
            'footer_enabled' => ['nullable', 'boolean'],
            'footer_copyright_text' => ['required', 'string', 'max:160'],
            'footer_background_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'footer_text_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'footer_secondary_text_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'footer_show_pcteckserv_credit' => ['nullable', 'boolean'],
            'footer_credit_text' => ['required', 'string', 'max:80'],
            'footer_pcteckserv_logo_media_id' => ['nullable', 'integer', 'exists:cms_media,id'],
            'footer_pcteckserv_logo_path' => ['nullable', 'string', 'max:255', 'not_regex:/^[A-Za-z]:\\\\/'],
            'footer_pcteckserv_url' => ['required', 'url', 'max:2048'],
            'footer_padding_y' => ['required', 'integer', 'between:8,96'],
            'footer_padding_x' => ['required', 'integer', 'between:8,96'],
            'footer_max_width' => ['required', 'integer', 'between:320,1920'],
        ];
    }

    public function validated($key = null, $default = null): mixed
    {
        $validated = parent::validated($key, $default);

        if ($key !== null) {
            return $validated;
        }

        $validated['footer_enabled'] = $this->boolean('footer_enabled');
        $validated['footer_show_pcteckserv_credit'] = $this->boolean('footer_show_pcteckserv_credit');
        $validated['footer_pcteckserv_logo_media_id'] = $validated['footer_pcteckserv_logo_media_id'] ?? null;
        $validated['footer_pcteckserv_logo_path'] = trim((string) ($validated['footer_pcteckserv_logo_path'] ?? ''), '/');

        return $validated;
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
