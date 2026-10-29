<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('cms_two_factor_authentications', function (Blueprint $table): void {
            $table->id();
            $table->morphs('user');
            $table->text('secret');
            $table->text('recovery_codes');
            $table->string('version', 64);
            $table->unsignedBigInteger('last_used_timestamp')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->unique(['user_type', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_two_factor_authentications');
    }
};
