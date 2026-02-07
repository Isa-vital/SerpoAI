<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watchlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('symbol', 20);           // BTC, AAPL, EURUSD, BTCUSDT
            $table->string('market_type', 10);       // crypto, stock, forex
            $table->string('label')->nullable();      // user-defined label
            $table->decimal('alert_above', 18, 8)->nullable();  // price alert threshold
            $table->decimal('alert_below', 18, 8)->nullable();  // price alert threshold
            $table->decimal('last_price', 18, 8)->nullable();
            $table->decimal('price_change_24h', 10, 4)->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'symbol']);
            $table->index('market_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watchlist_items');
    }
};
