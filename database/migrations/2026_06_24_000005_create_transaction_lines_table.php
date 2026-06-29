<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_lines', function (Blueprint $table) {
            $table->id();
            $table->integer('account_id');
            $table->string('reference');
            $table->string('reference_id', 36)->default('0');
            $table->integer('reference_sub_id')->default(0);
            $table->date('date');
            $table->decimal('credit', 15, 2)->default('0.00');
            $table->decimal('debit', 15, 2)->default('0.00');
            $table->integer('created_by')->default(0);
            $table->timestamps();

            $table->index('account_id');
            $table->index('date');
            $table->index(['reference', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_lines');
    }
};
