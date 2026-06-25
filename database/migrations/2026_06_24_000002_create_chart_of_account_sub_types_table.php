<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_of_account_sub_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('');
            $table->integer('type')->default(0);
            $table->timestamps();

            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_of_account_sub_types');
    }
};
