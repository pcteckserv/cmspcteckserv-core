<?php

namespace Pcteckserv\CmsCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Pcteckserv\CmsCore\Support\CssLength;

class UpdateFooterSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('footer.update-settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'footer_enabled' => ['nullable', 'boolean'],
            'footer_copyright_text' => ['required', 'string', 'max:160'],
            'footer_background_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'footer_text_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'footer_secondary_text_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'footer_show_pcteckserv_credit' => ['nullable', 'boolean'],
            'footer_credit_text' => ['required', 'string', 'max:80'],
            'footer_pcteckserv_logo_media_id' => ['nullable', 'integer', 'exists:cms_media,id'],
            'footer_pcteckserv_logo_path' => ['nullable', 'string', 'max:255', 'not_regex:/^[A-Za-z]:\\\\/'],
            'footer_pcteckserv_logo_scale' => ['required', 'integer', 'between:25,250'],
            'footer_pcteckserv_url' => ['required', 'url', 'max:2048'],
            'footer_padding_y' => ['required', 'string', 'max:80', 'regex:'.CssLength::PATTERN],
            'footer_padding_x' => ['required', 'string', 'max:80', 'regex:'.CssLength::PATTERN],
            'footer_max_width' => ['required', 'string', 'max:80', 'regex:'.CssLength::PATTERN],
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
        $validated['footer_pcteckserv_logo_scale'] = (int) $validated['footer_pcteckserv_logo_scale'];
        $validated['footer_padding_y'] = trim((string) $validated['footer_padding_y']);
        $validated['footer_padding_x'] = trim((string) $validated['footer_padding_x']);
        $validated['footer_max_width'] = trim((string) $validated['footer_max_width']);

        return $validated;
    }

}
