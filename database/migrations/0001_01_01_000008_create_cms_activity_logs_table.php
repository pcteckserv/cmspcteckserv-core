<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->nullableMorphs('user');
            $table->string('action')->index();
            $table->string('category')->nullable()->index();
            $table->text('description')->nullable();
            $table->nullableMorphs('subject');
            $table->string('ip_address', 45)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->text('url')->nullable();
            $table->string('http_method', 12)->nullable();
            $table->json('properties')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_activity_logs');
    }
};
