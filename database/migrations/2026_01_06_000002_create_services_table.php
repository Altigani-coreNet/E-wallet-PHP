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
        Schema::create('services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->constrained('service_categories')->onDelete('cascade');
            $table->foreignUuid('partner_id')->nullable()->constrained('partners')->onDelete('set null');
            $table->uuid('country_id')->nullable();
            $table->enum('service_type', ['digital', 'ivr', 'sms'])->default('digital');
            $table->json('service_name')->nullable();
            $table->string('image')->nullable();
            $table->json('description')->nullable();
            $table->enum('status', ['active', 'inactive', 'pending' , 'testing' , 'staging'])->default('active');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // $table->index(['category_id', 'is_active']);
            $table->index('partner_id');
            $table->index('country_id');
            $table->index('service_type');
            // $table->index('service_name');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};

