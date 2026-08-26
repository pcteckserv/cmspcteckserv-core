<?php

namespace Pcteckserv\CmsCore\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MediaValidator
{
    public function validate(UploadedFile $file): void
    {
        $extension = Str::lower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();
        $allowedExtensions = config('cms-core.media.allowed_extensions', []);
        $allowedMimes = config('cms-core.media.allowed_mimes', []);

        if (! in_array($extension, $allowedExtensions, true)) {
            throw ValidationException::withMessages([
                'files' => 'A extensão do ficheiro não é permitida.',
            ]);
        }

        if (! $mimeType || ! in_array($mimeType, $allowedMimes, true)) {
            throw ValidationException::withMessages([
                'files' => 'O tipo real do ficheiro não é permitido.',
            ]);
        }

        if (Str::contains($file->getClientOriginalName(), ['../', '..\\', '/', '\\'])) {
            throw ValidationException::withMessages([
                'files' => 'O nome do ficheiro contém caracteres inválidos.',
            ]);
        }

        if (preg_match('/\.(php|phtml|phar|cgi|pl|sh)(\.|$)/i', $file->getClientOriginalName()) === 1) {
            throw ValidationException::withMessages([
                'files' => 'Ficheiros executáveis não são permitidos.',
            ]);
        }

        if ($extension === 'svg' && ! config('cms-core.media.allow_svg', false)) {
            throw ValidationException::withMessages([
                'files' => 'SVG está bloqueado por defeito por motivos de segurança.',
            ]);
        }

        if ($extension === 'svg') {
            $this->validateSvg($file);
        }
    }

    private function validateSvg(UploadedFile $file): void
    {
        $contents = file_get_contents($file->getRealPath()) ?: '';

        if (preg_match('/<\s*script|on[a-z]+\s*=|javascript:|data:|xlink:href\s*=|href\s*=\s*["\']https?:/i', $contents) === 1) {
            throw ValidationException::withMessages([
                'files' => 'O SVG contém conteúdo potencialmente perigoso.',
            ]);
        }
    }
}
