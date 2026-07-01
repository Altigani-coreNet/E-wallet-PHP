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
        Schema::create('change_requests', function (Blueprint $table) {
            $table->id();
            // Customer/Merchant/Admin models use UUID primary keys
            $table->uuidMorphs('changeable');
            $table->nullableUuidMorphs('requester');
            $table->nullableUuidMorphs('approver');

            $table->json('payload');
            $table->boolean('has_file')->default(false);
            $table->string('reason')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');

            $table->string('moderation_note')->nullable();

            // Denormalized approver UUID (Admin uses HasUuids)
            $table->uuid('approved_id')->nullable()->index();

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('change_requests');
    }
};
