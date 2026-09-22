<?php

namespace Pcteckserv\CmsCore\Updates;

use Pcteckserv\CmsCore\Support\ComposerCommand;
use Symfony\Component\Process\Process;

class ComposerInstalledPackageReader
{
    private readonly ComposerCommand $composerCommand;

    public function __construct(?ComposerCommand $composerCommand = null)
    {
        $this->composerCommand = $composerCommand ?? new ComposerCommand();
    }

    /**
     * @return array<string, mixed>
     */
    public function read(string $package): array
    {
        $process = $this->run($this->composerCommand->build(['show', $package, '--format=json']));

        if (! $process->isSuccessful()) {
            return [];
        }

        $packageData = json_decode($process->getOutput(), true);

        if (! is_array($packageData)) {
            return [];
        }

        $packageData['version'] ??= $packageData['versions'][0] ?? null;

        return $packageData;
    }

    /**
     * @param array<int, string> $command
     */
    protected function run(array $command): Process
    {
        $process = new Process($command, base_path());
        $process->setTimeout(60);
        $process->run();

        return $process;
    }
}
