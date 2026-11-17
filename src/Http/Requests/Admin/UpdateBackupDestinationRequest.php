<?php

namespace Pcteckserv\CmsCore\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateBackupDestinationRequest extends FormRequest
{
    protected function failedValidation(Validator $validator): void
    {
        if ($this->expectsJson()) {
            throw new ValidationException($validator);
        }
        throw new ValidationException($validator, redirect($this->getRedirectUrl())
            ->withInput($this->except(['password', 'google_client_secret']))
            ->withErrors($validator, $this->errorBag));
    }

    public function authorize(): bool
    {
        return $this->user()?->can('backups.configure') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'disk' => ['nullable', 'required_if:protocol,local,sftp,s3,r2', 'string', 'max:64'],
            'protocol' => ['required', Rule::in(['local', 'ftp', 'ftps', 'sftp', 's3', 'r2', 'google'])],
            'host' => ['nullable', 'required_if:protocol,ftp,ftps,sftp,s3,r2', 'string', 'max:255', 'not_regex:/[\x00-\x20\x7f\/\\\\]/'],
            'port' => ['nullable', 'integer', 'between:1,65535'],
            'username' => ['nullable', 'required_if:protocol,ftp,ftps', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'remote_path' => ['required', 'string', 'max:255', 'not_regex:/\.\./', 'not_regex:/[\x00-\x1f\\\\]/'],
            'timeout' => ['required', 'integer', 'between:5,300'],
            'passive' => ['nullable', 'boolean'],
            'ssl' => ['nullable', 'boolean'],
            'verify_ssl' => ['nullable', 'boolean'],
            'ssh_fingerprint' => ['nullable', 'string', 'max:255'],
            'google_client_id' => ['nullable', 'required_if:protocol,google', 'string', 'max:512'],
            'google_client_secret' => ['nullable', 'string', 'max:2048'],
        ];
    }

    public function validated($key = null, $default = null): mixed
    {
        $validated = parent::validated($key, $default);

        if ($key !== null) {
            return $validated;
        }

        if (in_array($validated['protocol'], ['ftp', 'ftps'], true)) {
            $validated['passive'] = $this->boolean('passive');
            $validated['ssl'] = $validated['protocol'] === 'ftps' || $this->boolean('ssl');
        }
        if ($this->has('verify_ssl')) {
            $validated['verify_ssl'] = $this->boolean('verify_ssl');
        }
        if (in_array($validated['protocol'], ['ftp', 'ftps', 'google'], true)) {
            $validated['disk'] = 'local'; // Compatibilidade com a coluna existente; estes destinos usam ligação própria.
        }
        if (($validated['google_client_secret'] ?? '') === '') {
            unset($validated['google_client_secret']);
        }

        if (($validated['password'] ?? '') === '') {
            unset($validated['password']);
        }

        return $validated;
    }
}
