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
        Schema::create('change_histories', function (Blueprint $table) {
            $table->id();
            // Polymorphic target model (the model that was changed)
            $table->morphs('changeable'); // changeable_type, changeable_id
            // Polymorphic actor that performed the change (approver/admin)
            $table->nullableMorphs('actor'); // actor_type, actor_id

            // Also store the user_id directly for quick lookup
            $table->unsignedBigInteger('user_id')->nullable()->index();

            // Snapshot of the model before the change
            $table->json('before');
            // Snapshot of the model after the change
            $table->json('after');
            // The request payload that led to this change (for traceability)
            $table->json('payload')->nullable();

            // Source change request id (optional foreign key without constraint)
            $table->unsignedBigInteger('change_request_id')->nullable()->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('change_histories');
    }
};


