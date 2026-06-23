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
        Schema::create('service_forms', function (Blueprint $table) {
            $table->id();

            $table->foreignUuid('service_id')
                ->constrained('services')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            // Group-level info from "Mobile Services Form X"
            $table->string('form_name')->nullable();
            $table->string('form_url')->nullable();

            $table->uuid('country_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('service_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_forms');
    }
};

