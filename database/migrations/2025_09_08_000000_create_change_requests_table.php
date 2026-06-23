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
            // Polymorphic target model (the model to be changed)
            $table->morphs('changeable'); // changeable_type, changeable_id
            // Polymorphic requester (who requested the change)
            $table->nullableMorphs('requester'); // requester_type, requester_id
            // Optional approver (admin) who approved/rejected the change
            $table->nullableMorphs('approver'); // approver_type, approver_id

            // JSON payload with the fields to update (partial or full)
            $table->json('payload');
            // Whether this change request includes uploaded files
            $table->boolean('has_file')->default(false);
            // Optional description or reason
            $table->string('reason')->nullable();

            // Status: pending, approved, rejected, cancelled
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');

            // Optional message when approving/rejecting
            $table->string('moderation_note')->nullable();

            // Explicit approver id for convenience (in addition to morph approver)
            $table->unsignedBigInteger('approved_id')->nullable()->index();

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


