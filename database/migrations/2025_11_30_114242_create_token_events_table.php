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
        Schema::create('token_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type'); // 'buy', 'sell', 'liquidity_add', 'liquidity_remove', 'price_change', 'large_transfer'
            $table->string('tx_hash')->unique()->index();
            $table->string('from_address')->nullable();
            $table->string('to_address')->nullable();
            $table->decimal('amount', 20, 8)->nullable(); // Token amount
            $table->decimal('usd_value', 20, 8)->nullable(); // USD value at time of event
            $table->decimal('price_before', 20, 8)->nullable();
            $table->decimal('price_after', 20, 8)->nullable();
            $table->decimal('price_change_percent', 10, 2)->nullable();
            $table->text('details')->nullable(); // JSON details about the event
            $table->timestamp('event_timestamp');
            $table->boolean('notified')->default(false); // Whether alert was sent
            $table->timestamps();

            $table->index(['event_type', 'event_timestamp']);
            $table->index('notified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_events');
    }
};
