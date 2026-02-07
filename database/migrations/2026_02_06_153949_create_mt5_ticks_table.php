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
        Schema::create('mt5_ticks', function (Blueprint $table) {
            $table->id();
            $table->string('symbol');
            $table->decimal('bid', 10, 5);
            $table->decimal('ask', 10, 5);
            $table->decimal('spread', 10, 5)->nullable();
            $table->timestamp('tick_time');
            $table->timestamps();
            $table->index(['symbol', 'tick_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mt5_ticks');
    }
};
