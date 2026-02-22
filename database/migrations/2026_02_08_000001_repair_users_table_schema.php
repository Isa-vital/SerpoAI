<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repair migration: ensures the users table has the correct schema
 * for the Telegram bot, regardless of whether the original custom
 * migration ran or the default Laravel migration was used.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Core Telegram bot columns
            if (!Schema::hasColumn('users', 'telegram_id')) {
                $table->bigInteger('telegram_id')->unique()->after('id');
            }
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->after('telegram_id');
            }
            if (!Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable()->after('username');
            }
            if (!Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('users', 'language_code')) {
                $table->string('language_code')->default('en')->after('last_name');
            }
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('language_code');
            }
            if (!Schema::hasColumn('users', 'notifications_enabled')) {
                $table->boolean('notifications_enabled')->default(true)->after('is_active');
            }
            if (!Schema::hasColumn('users', 'language')) {
                $table->string('language', 5)->default('en')->after('notifications_enabled');
            }
            if (!Schema::hasColumn('users', 'preferences')) {
                $table->json('preferences')->nullable()->after('language');
            }
            if (!Schema::hasColumn('users', 'last_interaction_at')) {
                $table->timestamp('last_interaction_at')->nullable()->after('preferences');
            }
        });

        // Make default Laravel columns nullable if they exist (they're not used by the bot)
        if (Schema::hasColumn('users', 'name')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('name')->nullable()->change();
            });
        }
        if (Schema::hasColumn('users', 'email')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('email')->nullable()->change();
            });
        }
        if (Schema::hasColumn('users', 'password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('password')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // This is a repair migration — no rollback needed
    }
};
