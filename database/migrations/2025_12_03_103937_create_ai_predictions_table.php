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
        Schema::create('ai_predictions', function (Blueprint $table) {
            $table->id();
            $table->string('coin_symbol', 20)->index();
            $table->string('timeframe'); // 1h, 4h, 24h, 7d, 30d
            $table->string('prediction_type'); // price, trend, sentiment
            $table->decimal('current_price', 20, 8);
            $table->decimal('predicted_price', 20, 8)->nullable();
            $table->string('predicted_trend')->nullable(); // bullish, bearish, neutral
            $table->integer('confidence_score'); // 0-100
            $table->text('factors')->nullable(); // JSON of factors considered
            $table->text('ai_reasoning')->nullable(); // AI explanation
            $table->decimal('accuracy_score', 5, 2)->nullable(); // Post-prediction accuracy
            $table->timestamp('prediction_for')->nullable(); // Target time
            $table->boolean('is_validated')->default(false);
            $table->timestamps();

            $table->index(['coin_symbol', 'timeframe', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_predictions');
    }
};
