<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_media_collections', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('cms_media', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('collection_id')->nullable()->constrained('cms_media_collections')->nullOnDelete();
            $table->string('disk');
            $table->string('directory');
            $table->string('filename');
            $table->string('path')->unique();
            $table->string('optimized_path')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->string('original_filename');
            $table->string('extension', 20);
            $table->string('mime_type', 120);
            $table->string('media_type', 40)->index();
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('checksum', 64)->index();
            $table->string('alt_text')->nullable();
            $table->string('title')->nullable();
            $table->text('caption')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('optimization_status', 40)->default('pendente')->index();
            $table->unsignedBigInteger('original_size')->nullable();
            $table->unsignedBigInteger('optimized_size')->nullable();
            $table->json('variants')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cms_mediables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('media_id')->constrained('cms_media')->cascadeOnDelete();
            $table->morphs('mediable');
            $table->string('role')->nullable()->index();
            $table->unsignedInteger('position')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_mediables');
        Schema::dropIfExists('cms_media');
        Schema::dropIfExists('cms_media_collections');
    }
};
