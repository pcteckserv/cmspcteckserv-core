<?php

namespace Pcteckserv\CmsCore\Support;

use Symfony\Component\Process\Process;

class ProcessFailureOutput
{
    public function summarize(Process $process): string
    {
        // O Composer escreve também o progresso em stderr; o erro costuma estar no fim.
        $output = $process->getOutput()."\n".$process->getErrorOutput();
        $output = preg_replace('/\x1B\[[0-?]*[ -\/]*[@-~]/', '', $output) ?? '';
        $output = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $output) ?? '';

        $token = config('cms-core.updates.github_token');

        if (is_string($token) && $token !== '') {
            $output = str_replace([
                $token,
                rawurlencode($token),
                base64_encode('x-access-token:'.$token),
            ], '[credencial ocultada]', $output);
        }

        $output = preg_replace('~(https?://)[^\s/@]+(?::[^\s/@]*)?@~i', '$1[credencial ocultada]@', $output) ?? '';
        $output = preg_replace('/(Authorization:\s*(?:Basic|Bearer)\s+)\S+/i', '$1[credencial ocultada]', $output) ?? '';
        $output = str_replace([base_path(), str_replace('\\', '/', base_path())], '[aplicação]', $output);
        $output = trim(preg_replace('/\s+/u', ' ', $output) ?? '');

        if ($output === '') {
            return 'sem detalhe devolvido pelo processo.';
        }

        return mb_strlen($output) > 2400 ? '...'.mb_substr($output, -2400) : $output;
    }
}
