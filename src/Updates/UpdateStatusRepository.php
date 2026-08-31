<?php

namespace Pcteckserv\CmsCore\Updates;

use Illuminate\Support\Facades\Cache;

class UpdateStatusRepository
{
    private const TTL_SECONDS = 86400;

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $package): ?array
    {
        $status = Cache::get($this->key($package));

        return is_array($status) ? $status : null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        $statuses = [];

        foreach (config('cms-core.updates.packages', []) as $package) {
            if (! is_string($package)) {
                continue;
            }

            $status = $this->get($package);

            if ($status !== null) {
                $statuses[$package] = $status;
            }
        }

        return $statuses;
    }

    public function markQueued(string $package, ?int $userId = null): void
    {
        $this->put($package, 'queued', 'Atualização colocada na fila.', $userId);
    }

    public function markRunning(string $package, ?int $userId = null): void
    {
        $this->put($package, 'running', 'Atualização em execução.', $userId);
    }

    public function markFinished(string $package, UpdateResult $result, ?int $userId = null): void
    {
        $this->put(
            $package,
            $result->successful ? 'succeeded' : 'failed',
            $result->message,
            $userId,
        );
    }

    public function markFailed(string $package, string $message, ?int $userId = null): void
    {
        $this->put($package, 'failed', $message, $userId);
    }

    private function put(string $package, string $state, string $message, ?int $userId): void
    {
        Cache::put($this->key($package), [
            'state' => $state,
            'message' => $message,
            'user_id' => $userId,
            'updated_at' => now()->toDateTimeString(),
        ], self::TTL_SECONDS);
    }

    private function key(string $package): string
    {
        return 'cms-core:updates:status:'.sha1($package);
    }
}
