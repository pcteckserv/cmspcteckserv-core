<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_consent_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('version')->default(1);
            $table->boolean('banner_enabled')->default(true);
            $table->boolean('server_records_enabled')->default(false);
            $table->json('texts')->nullable();
            $table->json('published_config')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('cms_consent_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('public_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_required')->default(false);
            $table->string('color')->nullable();
            $table->string('icon')->nullable();
            $table->timestamps();
        });

        Schema::create('cms_consent_services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('cms_consent_categories')->nullOnDelete();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('provider')->nullable();
            $table->text('description')->nullable();
            $table->text('purpose')->nullable();
            $table->string('status')->default('active');
            $table->boolean('requires_consent')->default(true);
            $table->string('source')->default('manual');
            $table->unsignedTinyInteger('confidence')->default(0);
            $table->string('review_status')->default('requires_review');
            $table->json('found_on_urls')->nullable();
            $table->text('detection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('cms_consent_technologies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_id')->nullable()->constrained('cms_consent_services')->nullOnDelete();
            $table->string('type');
            $table->string('name')->nullable();
            $table->string('domain')->nullable();
            $table->string('path')->nullable();
            $table->string('duration')->nullable();
            $table->boolean('is_third_party')->default(false);
            $table->text('value')->nullable();
            $table->json('found_on_urls')->nullable();
            $table->timestamps();
            $table->index(['type', 'name']);
        });

        Schema::create('cms_consent_scans', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('pages_scanned')->default(0);
            $table->unsignedInteger('services_found')->default(0);
            $table->unsignedInteger('technologies_found')->default(0);
            $table->unsignedInteger('changes_found')->default(0);
            $table->json('urls')->nullable();
            $table->json('summary')->nullable();
            $table->text('error_log')->nullable();
            $table->timestamps();
        });

        Schema::create('cms_consent_scan_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('scan_id')->constrained('cms_consent_scans')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('cms_consent_services')->nullOnDelete();
            $table->string('type');
            $table->string('identifier');
            $table->string('domain')->nullable();
            $table->string('url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('cms_consent_records', function (Blueprint $table): void {
            $table->id();
            $table->uuid('anonymous_uuid')->index();
            $table->unsignedInteger('consent_version');
            $table->json('categories_json');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_consent_records');
        Schema::dropIfExists('cms_consent_scan_items');
        Schema::dropIfExists('cms_consent_scans');
        Schema::dropIfExists('cms_consent_technologies');
        Schema::dropIfExists('cms_consent_services');
        Schema::dropIfExists('cms_consent_categories');
        Schema::dropIfExists('cms_consent_settings');
    }
};
