<?php

namespace Pcteckserv\CmsCore\Services\Queues;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QueueMonitor
{
    public function overview(): array
    {
        $connection = config('queue.default', 'sync');
        $driver = config("queue.connections.{$connection}.driver", $connection);
        $jobsTable = config("queue.connections.{$connection}.table", 'jobs');
        $failedTable = config('queue.failed.table', 'failed_jobs');
        $batchesTable = config('queue.batching.table', 'job_batches');

        return [
            'connection' => $connection,
            'driver' => $driver,
            'jobs_table' => $jobsTable,
            'failed_table' => $failedTable,
            'batches_table' => $batchesTable,
            'supports_database_monitoring' => $driver === 'database' && Schema::hasTable($jobsTable),
            'supports_failed_jobs' => Schema::hasTable($failedTable),
            'supports_batches' => Schema::hasTable($batchesTable),
            'pending_jobs' => $this->pendingJobs($jobsTable, $driver),
            'reserved_jobs' => $this->reservedJobs($jobsTable, $driver),
            'failed_jobs' => $this->failedJobsCount($failedTable),
            'batches' => $this->batchesCount($batchesTable),
            'queues' => $this->queues($jobsTable, $driver),
            'recent_jobs' => $this->recentJobs($jobsTable, $driver),
            'recent_failed_jobs' => $this->recentFailedJobs($failedTable),
            'recent_batches' => $this->recentBatches($batchesTable),
            'recommended_local_command' => 'php artisan queue:work --queue=default --tries=3 --timeout=120',
            'recommended_supervisor_command' => 'php /caminho/do/site/artisan queue:work --sleep=3 --tries=3 --timeout=120',
        ];
    }

    private function pendingJobs(string $table, string $driver): int
    {
        if ($driver !== 'database' || ! Schema::hasTable($table)) {
            return 0;
        }

        return DB::table($table)->whereNull('reserved_at')->count();
    }

    private function reservedJobs(string $table, string $driver): int
    {
        if ($driver !== 'database' || ! Schema::hasTable($table)) {
            return 0;
        }

        return DB::table($table)->whereNotNull('reserved_at')->count();
    }

    private function failedJobsCount(string $table): int
    {
        return Schema::hasTable($table) ? DB::table($table)->count() : 0;
    }

    private function batchesCount(string $table): int
    {
        return Schema::hasTable($table) ? DB::table($table)->whereNull('finished_at')->count() : 0;
    }

    private function queues(string $table, string $driver): array
    {
        if ($driver !== 'database' || ! Schema::hasTable($table)) {
            return [];
        }

        return DB::table($table)
            ->select('queue', DB::raw('count(*) as total'))
            ->groupBy('queue')
            ->orderBy('queue')
            ->get()
            ->map(fn (object $queue): array => ['queue' => $queue->queue, 'total' => $queue->total])
            ->all();
    }

    private function recentJobs(string $table, string $driver): array
    {
        if ($driver !== 'database' || ! Schema::hasTable($table)) {
            return [];
        }

        return DB::table($table)
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (object $job): array => [
                'id' => $job->id,
                'queue' => $job->queue,
                'name' => $this->jobName($job->payload),
                'attempts' => $job->attempts,
                'reserved' => $job->reserved_at !== null,
                'available_at' => $this->formatTimestamp($job->available_at),
                'created_at' => $this->formatTimestamp($job->created_at),
            ])
            ->all();
    }

    private function recentFailedJobs(string $table): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        return DB::table($table)
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (object $job): array => [
                'id' => $job->id,
                'uuid' => $job->uuid,
                'connection' => $job->connection,
                'queue' => $job->queue,
                'name' => $this->jobName($job->payload),
                'failed_at' => $job->failed_at,
                'exception' => str($job->exception)->limit(260)->toString(),
            ])
            ->all();
    }

    private function recentBatches(string $table): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        return DB::table($table)
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(fn (object $batch): array => [
                'id' => $batch->id,
                'name' => $batch->name,
                'total_jobs' => $batch->total_jobs,
                'pending_jobs' => $batch->pending_jobs,
                'failed_jobs' => $batch->failed_jobs,
                'cancelled_at' => $this->formatTimestamp($batch->cancelled_at),
                'created_at' => $this->formatTimestamp($batch->created_at),
                'finished_at' => $this->formatTimestamp($batch->finished_at),
            ])
            ->all();
    }

    private function jobName(string $payload): string
    {
        $decoded = json_decode($payload, true);

        if (! is_array($decoded)) {
            return 'Job desconhecido';
        }

        return $decoded['displayName'] ?? data_get($decoded, 'data.commandName') ?? 'Job desconhecido';
    }

    private function formatTimestamp(mixed $timestamp): ?string
    {
        if (! is_numeric($timestamp) || (int) $timestamp <= 0) {
            return null;
        }

        return now()
            ->setTimestamp((int) $timestamp)
            ->timezone(config('cms-core.admin_timezone', 'Europe/Lisbon'))
            ->format('d/m/Y H:i:s');
    }
}
