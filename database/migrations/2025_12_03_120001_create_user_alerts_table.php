<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('alert_type'); // price, rsi, volume, divergence, cross, funding, whale
            $table->string('pair'); // BTCUSDT, ETHUSDT, etc.
            $table->string('condition'); // above, below, crosses_above, crosses_below, equals
            $table->string('value'); // Target value or threshold
            $table->string('timeframe')->nullable(); // 1m, 5m, 15m, 1h, 4h, 1d
            $table->boolean('is_active')->default(true);
            $table->timestamp('triggered_at')->nullable();
            $table->integer('trigger_count')->default(0);
            $table->boolean('repeat')->default(false); // Re-trigger after reset
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['alert_type', 'pair']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_alerts');
    }
};
