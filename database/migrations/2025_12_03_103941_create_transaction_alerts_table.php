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
        Schema::create('transaction_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('tx_hash', 100)->unique();
            $table->string('coin_symbol', 20)->index();
            $table->string('type'); // buy, sell, liquidity_add, liquidity_remove, transfer
            $table->string('from_address', 100)->nullable();
            $table->string('to_address', 100)->nullable();
            $table->decimal('amount', 30, 8);
            $table->decimal('amount_usd', 20, 2)->nullable();
            $table->decimal('price_impact', 8, 4)->nullable();
            $table->string('dex')->nullable(); // DeDust, StonFi, etc
            $table->boolean('is_whale')->default(false); // Large transaction
            $table->boolean('is_new_holder')->default(false);
            $table->text('metadata')->nullable(); // JSON additional data
            $table->boolean('notified')->default(false);
            $table->timestamp('transaction_time');
            $table->timestamps();

            $table->index(['coin_symbol', 'type', 'transaction_time']);
            $table->index(['is_whale', 'notified']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_alerts');
    }
};
