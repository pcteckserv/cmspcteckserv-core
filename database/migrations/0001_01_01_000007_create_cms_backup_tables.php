<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_backup_destinations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('disk')->default('local');
            $table->string('protocol')->default('local');
            $table->string('host')->nullable();
            $table->unsignedInteger('port')->nullable();
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->string('remote_path')->default('cms-backups');
            $table->unsignedInteger('timeout')->default(30);
            $table->boolean('passive')->default(true);
            $table->boolean('ssl')->default(false);
            $table->boolean('verify_ssl')->default(true);
            $table->string('ssh_fingerprint')->nullable();
            $table->string('connection_status')->default('not_tested');
            $table->timestamp('last_tested_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('cms_backup_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('destination_id')->nullable()->constrained('cms_backup_destinations')->nullOnDelete();
            $table->string('name');
            $table->boolean('enabled')->default(false);
            $table->string('type')->default('full');
            $table->string('frequency')->default('daily');
            $table->time('run_at')->default('02:00:00');
            $table->json('weekdays')->nullable();
            $table->unsignedTinyInteger('month_day')->nullable();
            $table->string('timezone')->default('Europe/Lisbon');
            $table->string('compression')->default('zip');
            $table->json('included_paths')->nullable();
            $table->json('excluded_paths')->nullable();
            $table->string('storage_mode')->default('local_and_remote');
            $table->unsignedInteger('retention_days')->nullable()->default(30);
            $table->unsignedInteger('retention_count')->nullable();
            $table->unsignedBigInteger('max_storage_bytes')->nullable();
            $table->json('notification_emails')->nullable();
            $table->json('notification_events')->nullable();
            $table->string('alert_timing')->default('after_retries');
            $table->unsignedInteger('repeat_alert_after_minutes')->default(360);
            $table->boolean('notify_recovery')->default(true);
            $table->timestamp('last_alert_sent_at')->nullable();
            $table->string('last_alert_signature')->nullable();
            $table->timestamp('last_success_notified_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamps();
        });

        Schema::create('cms_backup_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_id')->nullable()->constrained('cms_backup_plans')->nullOnDelete();
            $table->foreignId('destination_id')->nullable()->constrained('cms_backup_destinations')->nullOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('type');
            $table->string('origin')->default('manual');
            $table->string('status')->default('pending');
            $table->string('storage_mode')->default('local_and_remote');
            $table->string('filename')->nullable();
            $table->string('local_path')->nullable();
            $table->string('remote_path')->nullable();
            $table->unsignedBigInteger('size_before_compression')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('checksum_sha256')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->boolean('protected')->default(false);
            $table->text('failure_reason')->nullable();
            $table->json('manifest')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['plan_id', 'created_at']);
        });

        Schema::create('cms_backup_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreignId('backup_run_id')->nullable()->constrained('cms_backup_runs')->nullOnDelete();
            $table->string('action');
            $table->string('result')->default('success');
            $table->json('context')->nullable();
            $table->timestamps();
        });

        Schema::create('cms_backup_scheduler_heartbeats', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('ran_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_backup_scheduler_heartbeats');
        Schema::dropIfExists('cms_backup_audit_logs');
        Schema::dropIfExists('cms_backup_runs');
        Schema::dropIfExists('cms_backup_plans');
        Schema::dropIfExists('cms_backup_destinations');
    }
};
