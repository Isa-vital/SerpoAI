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
        Schema::create('sentiment_data', function (Blueprint $table) {
            $table->id();
            $table->string('coin_symbol', 20)->index();
            $table->string('source'); // twitter, telegram, reddit, etc
            $table->decimal('sentiment_score', 5, 2); // -100 to 100
            $table->integer('mention_count')->default(0);
            $table->integer('positive_mentions')->default(0);
            $table->integer('negative_mentions')->default(0);
            $table->integer('neutral_mentions')->default(0);
            $table->text('trending_keywords')->nullable(); // JSON
            $table->text('top_influencers')->nullable(); // JSON
            $table->decimal('social_volume_change_24h', 8, 2)->nullable();
            $table->text('sample_tweets')->nullable(); // JSON of sample data
            $table->timestamps();

            $table->index(['coin_symbol', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sentiment_data');
    }
};
