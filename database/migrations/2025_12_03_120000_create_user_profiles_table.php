<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('risk_level', ['conservative', 'moderate', 'aggressive', 'degen'])->default('moderate');
            $table->enum('trading_style', ['scalper', 'day_trader', 'swing_trader', 'hodler'])->default('day_trader');
            $table->json('favorite_pairs')->nullable(); // ['BTCUSDT', 'ETHUSDT', 'SERPO/TON']
            $table->json('watchlist')->nullable(); // Array of coins to monitor
            $table->string('timezone')->default('UTC');
            $table->boolean('notifications_enabled')->default(true);
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
