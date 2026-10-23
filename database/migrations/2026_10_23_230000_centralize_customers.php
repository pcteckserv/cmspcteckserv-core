<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Physical names remain stable to preserve existing foreign keys and credentials.
        if (! Schema::hasTable('cms_shop_customers')) {
            Schema::create('cms_shop_customers', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('email')->unique();
                $t->string('password');
                $t->string('phone')->nullable();
                $t->string('vat', 30)->nullable();
                $t->boolean('active')->default(true);
                $t->rememberToken();
                $t->timestamps();
            });
        }
        if (! Schema::hasColumn('cms_shop_customers', 'company')) {
            Schema::table('cms_shop_customers', fn (Blueprint $t) => $t->string('company', 190)->nullable());
        }
        Schema::create('cms_customer_groups', function (Blueprint $t) {
            $t->id();
            $t->string('name', 100)->unique();
            $t->text('description')->nullable();
            $t->timestamps();
        });
        Schema::create('cms_customer_group_members', function (Blueprint $t) {
            $t->foreignId('customer_id')->constrained('cms_shop_customers')->cascadeOnDelete();
            $t->foreignId('group_id')->constrained('cms_customer_groups')->cascadeOnDelete();
            $t->primary(['customer_id', 'group_id']);
        });
        if (! Schema::hasTable('cms_shop_addresses')) {
            Schema::create('cms_shop_addresses', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id')->nullable()->index();
                $t->foreignId('customer_id')->nullable()->constrained('cms_shop_customers')->restrictOnDelete();
                $t->string('label');
                $t->json('details');
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('cms_shop_password_resets')) {
            Schema::create('cms_shop_password_resets', function (Blueprint $t) {
                $t->string('email')->primary();
                $t->string('token');
                $t->timestamp('created_at')->nullable();
            });
        }
        Schema::create('cms_account_factors', function (Blueprint $t) {
            $t->id();
            $t->string('guard', 30);
            $t->unsignedBigInteger('account_id');
            $t->text('secret');
            $t->text('recovery_codes')->nullable();
            $t->timestamp('confirmed_at')->nullable();
            $t->unsignedBigInteger('last_step')->nullable();
            $t->timestamps();
            $t->unique(['guard', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_account_factors');
        Schema::dropIfExists('cms_customer_group_members');
        Schema::dropIfExists('cms_customer_groups');
        // Shared contacts, addresses and credentials must survive plugin/Core rollbacks.
    }
};
