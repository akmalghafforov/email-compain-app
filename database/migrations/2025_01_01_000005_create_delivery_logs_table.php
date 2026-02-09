<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignId('subscriber_id')->constrained('subscribers')->cascadeOnDelete();
            $table->string('channel', 50);
            $table->string('event', 30);
            $table->jsonb('payload')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['campaign_id', 'event']);
            $table->index('subscriber_id', 'idx_delivery_logs_subscriber_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_logs');
    }
};
