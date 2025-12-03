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
        Schema::create('analytics_reports', function (Blueprint $table) {
            $table->id();
            $table->string('coin_symbol', 20)->index();
            $table->string('report_type'); // daily, weekly, monthly
            $table->date('report_date')->index();
            $table->decimal('opening_price', 20, 8)->nullable();
            $table->decimal('closing_price', 20, 8)->nullable();
            $table->decimal('high_price', 20, 8)->nullable();
            $table->decimal('low_price', 20, 8)->nullable();
            $table->decimal('price_change_percent', 8, 2)->nullable();
            $table->decimal('volume_total', 30, 2)->nullable();
            $table->decimal('volume_change_percent', 8, 2)->nullable();
            $table->integer('holder_count')->nullable();
            $table->integer('holder_growth')->nullable();
            $table->decimal('tokens_burned', 30, 8)->nullable();
            $table->decimal('total_supply', 30, 8)->nullable();
            $table->integer('transaction_count')->nullable();
            $table->integer('new_holders')->nullable();
            $table->text('top_trades')->nullable(); // JSON
            $table->text('summary_text')->nullable(); // AI-generated summary
            $table->timestamps();

            $table->unique(['coin_symbol', 'report_type', 'report_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_reports');
    }
};
