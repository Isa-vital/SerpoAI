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
        Schema::create('signals', function (Blueprint $table) {
            $table->id();
            $table->string('coin_symbol');
            $table->string('signal_type');
            $table->string('indicator');
            $table->decimal('confidence', 5, 2);
            $table->decimal('price_at_signal', 20, 8);
            $table->text('reasoning')->nullable();
            $table->json('technical_data')->nullable();
            $table->boolean('is_sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['coin_symbol', 'created_at']);
            $table->index(['signal_type', 'is_sent']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signals');
    }
};
