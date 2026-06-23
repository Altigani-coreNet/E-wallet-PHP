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
        // Available webhook events (like Stripe events)
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "transaction.created"
            $table->string('category'); // e.g., "Transaction", "Settlement", "Batch"
            $table->string('version')->default('v1'); // e.g., "v1", "v2"
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['name', 'version']);
        });

        // Pivot table for webhook and events (many-to-many)
        Schema::create('webhook_event_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->constrained('webhooks')->onDelete('cascade');
            $table->foreignId('webhook_event_id')->constrained('webhook_events')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['webhook_id', 'webhook_event_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_event_subscriptions');
        Schema::dropIfExists('webhook_events');
    }
};

