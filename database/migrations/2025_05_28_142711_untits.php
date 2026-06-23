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

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('shop_id')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->string('code');
            $table->string('name');
            $table->foreignId('base_unit_id')->nullable()->constrained((new \App\Models\Unit())->getTable());
            $table->string('operator')->nullable();
            $table->double('operation_value')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
