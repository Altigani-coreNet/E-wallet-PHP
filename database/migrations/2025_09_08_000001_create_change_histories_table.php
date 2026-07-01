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
            $table->uuidMorphs('changeable');
            $table->nullableUuidMorphs('actor');

            // Actor UUID for quick lookup (Admin/Customer use HasUuids)
            $table->uuid('user_id')->nullable()->index();

            $table->json('before');
            $table->json('after');
            $table->json('payload')->nullable();

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
