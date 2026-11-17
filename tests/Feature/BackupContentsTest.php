<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Pcteckserv\CmsCore\Models\BackupDestination;
use Pcteckserv\CmsCore\Models\BackupPlan;
use Pcteckserv\CmsCore\Models\BackupRun;
use Pcteckserv\CmsCore\Services\Backups\BackupManager;
use Pcteckserv\CmsCore\Services\Backups\BackupRetentionService;
use Pcteckserv\CmsCore\Services\Backups\BackupStorageService;
use Pcteckserv\CmsCore\Services\Backups\BackupVerificationService;
use Tests\TestCase;
use ZipArchive;

class BackupContentsTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        if ($binary = getenv('CMS_BACKUPS_TEST_DUMP_BINARY')) {
            config(['cms-backups.database_dump_binary' => $binary, 'cms-backups.database_dump_options' => ['--skip-ssl']]);
        }
    }

    public function test_count_only_retention_keeps_the_newest_backups(): void
    {
        Storage::fake('local');
        $plan = BackupPlan::create(['name' => 'Contagem', 'retention_days' => null, 'retention_count' => 2]);
        for ($i = 1; $i <= 4; $i++) {
            Storage::disk('local')->put('cms-backups/'.$i.'.zip', 'backup');
            BackupRun::create(['plan_id' => $plan->id, 'type' => 'full', 'status' => 'success', 'local_path' => 'cms-backups/'.$i.'.zip', 'created_at' => now()->subDays(5 - $i)]);
        }
        $this->assertSame(2, app(BackupRetentionService::class)->apply($plan));
        $this->assertSame(['cms-backups/3.zip', 'cms-backups/4.zip'], $plan->runs()->orderBy('id')->pluck('local_path')->all());
        Storage::disk('local')->assertExists('cms-backups/4.zip');
        Storage::disk('local')->assertMissing('cms-backups/1.zip');
    }

    public function test_database_backup_contains_a_restorable_database(): void
    {
        $sqlite = config('database.default') === 'sqlite';
        Storage::fake('local');
        $plan = BackupPlan::create(['name' => 'Base', 'storage_mode' => 'local', 'retention_days' => null]);
        $run = app(BackupManager::class)->create($plan->fresh(), 'database', storageMode: 'local');
        $this->assertSame('success', $run->status);
        $zip = new ZipArchive;
        $zip->open(Storage::disk('local')->path($run->local_path));
        $contents = $zip->getFromName($sqlite ? 'database/database.sqlite' : 'database/database.sql');
        $zip->close();
        $this->assertIsString($contents);
        $this->assertGreaterThanOrEqual(0, $run->duration_seconds);
        if (! $sqlite) {
            $restoreDatabase = 'cms_core_test_restore_'.bin2hex(random_bytes(4));
            $connection = DB::connection();
            $pdo = $connection->getPdo();
            $source = $connection->getDatabaseName();
            $this->assertMatchesRegularExpression('/^cms_core_test_[a-z0-9_]+$/D', $source);
            try {
                $pdo->exec('CREATE DATABASE `'.$restoreDatabase.'`');
                $pdo->exec('USE `'.$restoreDatabase.'`');
                $pdo->exec($contents);
                $this->assertSame('Base', $pdo->query('SELECT name FROM cms_backup_plans LIMIT 1')->fetchColumn());
            } finally {
                $pdo->exec('USE `'.$source.'`');
                $pdo->exec('DROP DATABASE IF EXISTS `'.$restoreDatabase.'`');
            }

            return;
        }
        $this->assertStringStartsWith("SQLite format 3\0", $contents);
        $restore = tempnam(sys_get_temp_dir(), 'cms-restore-');
        try {
            file_put_contents($restore, $contents);
            $pdo = new \PDO('sqlite:'.$restore);
            $this->assertSame('Base', $pdo->query('SELECT name FROM cms_backup_plans LIMIT 1')->fetchColumn());
            $pdo = null;
        } finally {
            @unlink($restore);
        }
    }

    public function test_images_and_full_backups_have_distinct_contents_and_exclude_secrets(): void
    {
        Storage::fake('local');
        $relative = 'storage/app/backup-fixture-'.Str::uuid();
        $directory = base_path($relative);
        File::ensureDirectoryExists($directory.'/images');
        File::ensureDirectoryExists($directory.'/.git');
        File::put($directory.'/images/photo.jpg', 'photo');
        File::put($directory.'/images/document.pdf', 'document');
        File::put($directory.'/site.php', '<?php echo "site";');
        File::put($directory.'/.env', 'secret');
        File::put($directory.'/.git/config', 'private git configuration');
        config(['cms-backups.media_paths' => [$relative.'/images'], 'cms-backups.full_included_paths' => [$relative], 'cms-backups.default_excluded_paths' => [$relative.'/.git']]);
        $plan = BackupPlan::create(['name' => 'Conteúdo', 'storage_mode' => 'local', 'retention_days' => null]);
        try {
            $images = app(BackupManager::class)->create($plan->fresh(), 'images', storageMode: 'local');
            $this->assertSame('success', $images->status);
            $zip = new ZipArchive;
            $zip->open(Storage::disk('local')->path($images->local_path));
            $this->assertNotFalse($zip->locateName('files/'.$relative.'/images/photo.jpg'));
            $this->assertFalse($zip->locateName('files/'.$relative.'/images/document.pdf'));
            $this->assertFalse($zip->locateName('files/'.$relative.'/site.php'));
            $zip->close();
            $full = app(BackupManager::class)->create($plan->fresh(), 'full', storageMode: 'local');
            $this->assertSame('success', $full->status);
            $zip->open(Storage::disk('local')->path($full->local_path));
            $this->assertNotFalse($zip->locateName('files/'.$relative.'/site.php'));
            $this->assertNotFalse($zip->locateName('files/'.$relative.'/images/photo.jpg'));
            $this->assertFalse($zip->locateName('files/'.$relative.'/.env'));
            $this->assertFalse($zip->locateName('files/'.$relative.'/.git/config'));
            $this->assertNotFalse($zip->locateName(config('database.default') === 'sqlite' ? 'database/database.sqlite' : 'database/database.sql'));
            $zip->close();
        } finally {
            $this->assertStringStartsWith(str_replace('\\', '/', storage_path('app')).'/', str_replace('\\', '/', $directory));
            File::deleteDirectory($directory);
        }
    }

    public function test_remote_only_backup_is_verified_and_deleted_using_its_original_destination(): void
    {
        Storage::fake('local');
        Storage::fake('backup-remote');
        $destination = BackupDestination::create(['name' => 'Remoto', 'protocol' => 'local', 'disk' => 'backup-remote', 'remote_path' => 'original']);
        $plan = BackupPlan::create(['name' => 'Remoto', 'destination_id' => $destination->id, 'storage_mode' => 'remote', 'retention_days' => null]);
        $run = app(BackupManager::class)->create($plan->fresh(), 'database', storageMode: 'remote');
        $this->assertSame('success', $run->status);
        $this->assertNull($run->local_path);
        Storage::disk('backup-remote')->assertExists($run->remote_path);
        $destination->update(['remote_path' => 'changed']);
        $this->assertTrue(app(BackupVerificationService::class)->verify($run->fresh()));
        app(BackupRetentionService::class)->delete($run->fresh());
        Storage::disk('backup-remote')->assertMissing($run->remote_path);
        $this->assertNull(BackupRun::find($run->id));
    }

    public function test_remote_failure_preserves_local_copy_and_previous_successful_backups(): void
    {
        Storage::fake('local');
        $plan = BackupPlan::create(['name' => 'Falha remota', 'storage_mode' => 'remote', 'retention_count' => 1, 'retention_days' => null]);
        Storage::disk('local')->put('cms-backups/previous.zip', 'old');
        $old = BackupRun::create(['plan_id' => $plan->id, 'type' => 'full', 'status' => 'success', 'local_path' => 'cms-backups/previous.zip']);
        $run = app(BackupManager::class)->create($plan->fresh(), 'database', storageMode: 'remote');
        $this->assertSame('failed', $run->status);
        $this->assertNotNull($old->fresh());
        Storage::disk('local')->assertExists($run->local_path);
        Storage::disk('local')->assertExists('cms-backups/previous.zip');
    }

    public function test_protected_backups_survive_retention_and_no_limits_delete_nothing(): void
    {
        Storage::fake('local');
        $plan = BackupPlan::create(['name' => 'Protegidos', 'retention_days' => null, 'retention_count' => null]);
        $protected = BackupRun::create(['plan_id' => $plan->id, 'type' => 'full', 'status' => 'success', 'protected' => true]);
        $this->assertSame(0, app(BackupRetentionService::class)->apply($plan));
        $plan->update(['retention_count' => 1]);
        $this->assertSame(0, app(BackupRetentionService::class)->apply($plan));
        $this->assertNotNull($protected->fresh());
        $this->expectException(\RuntimeException::class);
        app(BackupRetentionService::class)->delete($protected);
    }

    public function test_retention_deletion_failure_keeps_local_files_and_history(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('cms-backups/keep.zip', 'backup');
        $destination = BackupDestination::create(['name' => 'Remoto', 'protocol' => 'local', 'disk' => 'local', 'remote_path' => 'remote']);
        $run = BackupRun::create(['destination_id' => $destination->id, 'type' => 'full', 'status' => 'success', 'local_path' => 'cms-backups/keep.zip', 'remote_path' => 'remote/keep.zip']);
        $storage = \Mockery::mock(BackupStorageService::class);
        $storage->shouldReceive('delete')->once()->andThrow(new \RuntimeException('Falha remota'));
        try {
            (new BackupRetentionService($storage))->delete($run);
            $this->fail('A eliminação devia falhar.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Falha remota', $exception->getMessage());
        }
        $this->assertNotNull($run->fresh());
        Storage::disk('local')->assertExists('cms-backups/keep.zip');
    }
}
