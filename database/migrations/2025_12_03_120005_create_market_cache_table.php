<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_cache', function (Blueprint $table) {
            $table->id();
            $table->string('cache_key')->unique(); // market_scan, btcusdt_data, rsi_heatmap, etc.
            $table->string('data_type'); // scan, price, indicator, news, etc.
            $table->json('data'); // Cached market data
            $table->integer('ttl')->default(300); // Time to live in seconds
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['cache_key', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_cache');
    }
};
