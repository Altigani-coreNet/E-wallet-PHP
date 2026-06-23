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
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('service_id')->constrained('services')->onDelete('cascade');
            $table->foreignUuid('service_sub_category_id')->nullable()->constrained('service_sub_categories')->nullOnDelete();
            $table->foreignUuid('type_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->json('name')->nullable();
            $table->json('description')->nullable();
            // $table->json('description')->nullable()->after('name');

            $table->string('service_url')->nullable();
            $table->string('notify_url')->nullable();
            $table->string('prepay_url')->nullable();
            $table->string('image')->nullable();
            $table->boolean('status')->default(1)->nullable();
            $table->uuid('country_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('service_id');
            $table->index('country_id');
            $table->index('service_sub_category_id');
            $table->index('type_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
