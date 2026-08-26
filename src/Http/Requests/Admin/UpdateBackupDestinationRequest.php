<?php

namespace Pcteckserv\CmsCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBackupDestinationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('backups.configure') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'disk' => ['required', 'string', 'max:64'],
            'protocol' => ['required', Rule::in(['local', 'ftp', 'ftps', 'sftp', 's3', 'r2'])],
            'host' => ['nullable', 'required_unless:protocol,local', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'between:1,65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'remote_path' => ['required', 'string', 'max:255', 'not_regex:/\.\./'],
            'timeout' => ['required', 'integer', 'between:5,300'],
            'passive' => ['nullable', 'boolean'],
            'ssl' => ['nullable', 'boolean'],
            'verify_ssl' => ['nullable', 'boolean'],
            'ssh_fingerprint' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function validated($key = null, $default = null): mixed
    {
        $validated = parent::validated($key, $default);

        if ($key !== null) {
            return $validated;
        }

        $validated['passive'] = $this->boolean('passive');
        $validated['ssl'] = $this->boolean('ssl');
        $validated['verify_ssl'] = $this->boolean('verify_ssl');

        if (($validated['password'] ?? '') === '') {
            unset($validated['password']);
        }

        return $validated;
    }
}
