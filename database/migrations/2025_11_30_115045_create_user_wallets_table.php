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
        Schema::create('user_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('wallet_address')->index();
            $table->string('label')->nullable(); // Optional wallet nickname
            $table->decimal('balance', 20, 8)->default(0); // SERPO balance
            $table->decimal('usd_value', 20, 8)->default(0); // Cached USD value
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            // Prevent duplicate wallet addresses per user
            $table->unique(['user_id', 'wallet_address']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_wallets');
    }
};
