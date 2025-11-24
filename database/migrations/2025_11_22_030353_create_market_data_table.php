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
        Schema::create('market_data', function (Blueprint $table) {
            $table->id();
            $table->string('coin_symbol');
            $table->decimal('price', 20, 8);
            $table->decimal('price_change_24h', 10, 2)->nullable();
            $table->bigInteger('volume_24h')->nullable();
            $table->bigInteger('market_cap')->nullable();
            $table->decimal('rsi', 5, 2)->nullable();
            $table->decimal('macd', 20, 8)->nullable();
            $table->decimal('macd_signal', 20, 8)->nullable();
            $table->decimal('ema_12', 20, 8)->nullable();
            $table->decimal('ema_26', 20, 8)->nullable();
            $table->json('additional_data')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['coin_symbol', 'recorded_at']);
            $table->index('recorded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_data');
    }
};
