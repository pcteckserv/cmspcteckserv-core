<?php

namespace Tests\Feature;

use Mockery;
use Pcteckserv\CmsCore\Support\ProcessFailureOutput;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ProcessFailureOutputTest extends TestCase
{
    public function test_preserves_final_error_after_long_composer_progress(): void
    {
        $result = $this->summarize('', str_repeat("Loading repositories\x08\x1B[2K\r\n", 200)
            ."\x1B[31mPermission denied ao sincronizar o repositório.\x1B[0m");

        $this->assertStringEndsWith('Permission denied ao sincronizar o repositório.', $result);
        $this->assertLessThanOrEqual(2403, mb_strlen($result));
        $this->assertStringNotContainsString("\x08", $result);
        $this->assertStringNotContainsString("\x1B", $result);
    }

    public function test_includes_stdout_error_when_stderr_only_contains_progress(): void
    {
        $result = $this->summarize('Erro: dependência incompatível.', 'Progresso da instalação.');

        $this->assertStringContainsString('Erro: dependência incompatível.', $result);
        $this->assertStringContainsString('Progresso da instalação.', $result);
    }

    public function test_redacts_credentials_and_application_path_before_displaying_output(): void
    {
        $token = 'credencial-ficticia-de-teste';
        config(['cms-core.updates.github_token' => $token]);
        $result = $this->summarize('', implode("\n", [
            $token,
            base64_encode('x-access-token:'.$token),
            'https://utilizador:palavra-passe@example.test/repo.git',
            'Authorization: Bearer exemplo-ficticio',
            base_path('vendor/composer'),
        ]));

        foreach ([$token, base64_encode('x-access-token:'.$token), 'palavra-passe', 'exemplo-ficticio', base_path()] as $secret) {
            $this->assertStringNotContainsString($secret, $result);
        }

        $this->assertStringContainsString('[credencial ocultada]', $result);
        $this->assertStringContainsString('[aplicação]', $result);
    }

    public function test_handles_empty_output(): void
    {
        $this->assertSame('sem detalhe devolvido pelo processo.', $this->summarize('', "\x08\r\n"));
    }

    private function summarize(string $stdout, string $stderr): string
    {
        $process = Mockery::mock(Process::class);
        $process->shouldReceive('getOutput')->once()->andReturn($stdout);
        $process->shouldReceive('getErrorOutput')->once()->andReturn($stderr);

        return (new ProcessFailureOutput())->summarize($process);
    }
}
