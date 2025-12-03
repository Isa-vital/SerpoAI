<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('premium_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('tier', ['free', 'basic', 'pro', 'vip'])->default('free');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('payment_method')->nullable(); // crypto, stars, subscription
            $table->string('transaction_hash')->nullable(); // For crypto payments
            $table->integer('scan_limit')->default(10); // Scans per day
            $table->integer('alert_limit')->default(5); // Active alerts allowed
            $table->json('features')->nullable(); // Enabled features array
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['is_active', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('premium_subscriptions');
    }
};
