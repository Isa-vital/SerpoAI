<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('symbol', 20);            // BTCUSDT, AAPL, EURUSD
            $table->string('market_type', 10);        // crypto, stock, forex
            $table->enum('side', ['long', 'short'])->default('long');
            $table->decimal('quantity', 20, 8);       // amount of asset
            $table->decimal('entry_price', 18, 8);    // avg entry price
            $table->decimal('current_price', 18, 8)->nullable();
            $table->decimal('unrealized_pnl', 18, 4)->nullable();
            $table->decimal('unrealized_pnl_pct', 10, 4)->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->decimal('exit_price', 18, 8)->nullable();
            $table->decimal('realized_pnl', 18, 4)->nullable();
            $table->decimal('realized_pnl_pct', 10, 4)->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'symbol']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_positions');
    }
};
