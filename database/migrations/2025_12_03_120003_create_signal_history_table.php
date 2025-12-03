<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signal_history', function (Blueprint $table) {
            $table->id();
            $table->string('pair');
            $table->enum('direction', ['bullish', 'bearish', 'neutral']);
            $table->integer('confidence_score'); // 0-100
            $table->enum('style', ['scalp', 'intraday', 'swing', 'avoid']);
            $table->enum('risk_level', ['low', 'medium', 'high', 'degen']);
            $table->decimal('entry_price', 20, 8)->nullable();
            $table->json('indicators'); // RSI, trend, SR, OI, volume, funding, divergences
            $table->text('reasoning')->nullable(); // AI explanation
            $table->integer('view_count')->default(0);
            $table->timestamps();

            $table->index(['pair', 'created_at']);
            $table->index(['direction', 'confidence_score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signal_history');
    }
};
