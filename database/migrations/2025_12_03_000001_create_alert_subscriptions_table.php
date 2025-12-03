<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alert_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id')->index(); // Telegram chat ID (can be user or group)
            $table->string('chat_type')->default('private'); // private, group, supergroup, channel
            $table->string('chat_title')->nullable(); // Group/channel name if applicable
            $table->json('alert_types')->nullable(); // ['buy', 'whale', 'price_change', 'liquidity'] or null for all
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_alert_sent_at')->nullable();
            $table->timestamps();

            // Unique constraint on chat_id
            $table->unique('chat_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_subscriptions');
    }
};
