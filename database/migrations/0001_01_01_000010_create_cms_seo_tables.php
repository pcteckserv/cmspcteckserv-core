<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('seo_meta', function (Blueprint $table): void {
            $table->id();
            $table->morphs('seoable');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('canonical_url', 2048)->nullable();
            $table->boolean('robots_index')->default(true);
            $table->boolean('robots_follow')->default(true);
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image', 2048)->nullable();
            $table->string('og_type')->default('website');
            $table->string('twitter_title')->nullable();
            $table->text('twitter_description')->nullable();
            $table->string('twitter_image', 2048)->nullable();
            $table->string('twitter_card')->default('summary_large_image');
            $table->string('schema_type')->nullable();
            $table->json('schema_data')->nullable();
            $table->boolean('exclude_from_sitemap')->default(false);
            $table->timestamps();
        });

        Schema::create('seo_redirects', function (Blueprint $table): void {
            $table->id();
            $table->string('source', 2048)->unique();
            $table->string('destination', 2048);
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('seo_not_found_errors', function (Blueprint $table): void {
            $table->id();
            $table->string('url', 2048);
            $table->string('method', 10);
            $table->string('referer', 2048)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('ip_hash')->nullable();
            $table->unsignedBigInteger('hits')->default(0);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->boolean('is_ignored')->default(false)->index();
            $table->boolean('is_resolved')->default(false)->index();
            $table->timestamps();
            $table->unique(['url', 'method']);
        });

        Schema::create('seo_audits', function (Blueprint $table): void {
            $table->id();
            $table->string('url', 2048)->index();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->unsignedTinyInteger('score')->default(0);
            $table->json('results')->nullable();
            $table->timestamp('scanned_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_audits');
        Schema::dropIfExists('seo_not_found_errors');
        Schema::dropIfExists('seo_redirects');
        Schema::dropIfExists('seo_meta');
    }
};
