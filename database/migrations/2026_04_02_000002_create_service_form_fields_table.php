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
        Schema::create('service_form_fields', function (Blueprint $table) {
            $table->id();

            $table->foreignId('service_form_id')
                ->constrained('service_forms')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->string('label_en')->nullable();
            $table->string('label_ar')->nullable();
            $table->string('key');
            $table->string('type');
            $table->json('options_json')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->boolean('status')->default(true);
            $table->uuid('country_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['service_form_id', 'sort_order'], 'service_form_fields_order_idx');
            $table->unique(['service_form_id', 'key', 'deleted_at'], 'service_form_fields_unique_key_per_form');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_form_fields');
    }
};

