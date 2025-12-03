<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scan_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('scan_type'); // market_scan, pair_analysis, rsi_heatmap, divergence, etc.
            $table->string('pair')->nullable(); // NULL for market-wide scans
            $table->json('parameters')->nullable(); // Timeframes, filters, etc.
            $table->json('results')->nullable(); // Cached scan results
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('scan_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_history');
    }
};
