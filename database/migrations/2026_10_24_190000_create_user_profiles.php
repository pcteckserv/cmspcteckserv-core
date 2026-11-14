<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A Agenda de algumas distribuições 3.0.x já preservava estes perfis.
        if (Schema::hasTable('cms_user_profiles')) {
            if (!Schema::hasColumns('cms_user_profiles', ['id', 'user_id', 'avatar_path', 'biography', 'created_at', 'updated_at'])) {
                throw new RuntimeException('A tabela de perfis existente não tem a estrutura esperada. Os dados foram preservados; valide a instalação antes de atualizar.');
            }
            return;
        }
        Schema::create('cms_user_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('avatar_path')->nullable();
            $table->text('biography')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_user_profiles');
    }
};
