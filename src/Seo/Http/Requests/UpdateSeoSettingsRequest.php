<?php

namespace Pcteckserv\CmsCore\Seo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSeoSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('seo.settings.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'seo_site_name' => ['required', 'string', 'max:120'],
            'seo_default_title' => ['required', 'string', 'max:120'],
            'seo_title_template' => ['required', 'string', 'max:160'],
            'seo_default_description' => ['nullable', 'string', 'max:320'],
            'seo_default_og_image' => ['nullable', 'url', 'max:2048'],
            'seo_twitter_card' => ['required', 'in:summary,summary_large_image'],
            'seo_default_robots_index' => ['nullable', 'boolean'],
            'seo_default_robots_follow' => ['nullable', 'boolean'],
            'seo_auto_canonical' => ['nullable', 'boolean'],
            'seo_base_url' => ['required', 'url', 'max:2048'],
            'seo_organization_name' => ['nullable', 'string', 'max:160'],
            'seo_organization_type' => ['required', 'string', 'max:80'],
            'seo_organization_logo' => ['nullable', 'url', 'max:2048'],
            'seo_organization_phone' => ['nullable', 'string', 'max:80'],
            'seo_organization_email' => ['nullable', 'email', 'max:160'],
            'seo_organization_address' => ['nullable', 'string', 'max:500'],
            'seo_social_profiles' => ['nullable', 'string', 'max:4000'],
            'seo_search_console_verification' => ['nullable', 'string', 'max:255'],
            'seo_bing_verification' => ['nullable', 'string', 'max:255'],
            'seo_generate_open_graph' => ['nullable', 'boolean'],
            'seo_generate_twitter_cards' => ['nullable', 'boolean'],
            'seo_generate_json_ld' => ['nullable', 'boolean'],
            'seo_generate_sitemap' => ['nullable', 'boolean'],
            'seo_generate_robots_txt' => ['nullable', 'boolean'],
            'seo_robots_allow' => ['nullable', 'string', 'max:4000'],
            'seo_robots_disallow' => ['nullable', 'string', 'max:4000', 'not_regex:/^\s*\/\s*$/m'],
            'seo_robots_sitemap_url' => ['nullable', 'url', 'max:2048'],
            'seo_robots_advanced' => ['nullable', 'string', 'max:8000', 'not_regex:/Disallow:\s*\/\s*$/mi'],
        ];
    }

    public function validated($key = null, $default = null): mixed
    {
        $validated = parent::validated($key, $default);

        if ($key !== null) {
            return $validated;
        }

        foreach (['seo_default_robots_index', 'seo_default_robots_follow', 'seo_auto_canonical', 'seo_generate_open_graph', 'seo_generate_twitter_cards', 'seo_generate_json_ld', 'seo_generate_sitemap', 'seo_generate_robots_txt'] as $field) {
            $validated[$field] = $this->boolean($field);
        }

        return $validated;
    }
}
