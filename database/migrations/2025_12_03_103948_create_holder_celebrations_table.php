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
        Schema::create('holder_celebrations', function (Blueprint $table) {
            $table->id();
            $table->string('coin_symbol', 20)->index();
            $table->string('milestone_type'); // holder_count, price, volume, etc
            $table->integer('milestone_value');
            $table->string('celebration_message');
            $table->string('gif_url')->nullable();
            $table->boolean('celebrated')->default(false);
            $table->timestamp('celebrated_at')->nullable();
            $table->timestamps();

            $table->index(['coin_symbol', 'celebrated']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holder_celebrations');
    }
};
