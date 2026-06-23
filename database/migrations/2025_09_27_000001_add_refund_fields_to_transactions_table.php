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
        Schema::table('transactions', function (Blueprint $table) {
            // Add refund and void fields
            $table->decimal('refunded_amount', 10, 2)->nullable()->after('amount');
            $table->decimal('voided_amount', 10, 2)->nullable()->after('refunded_amount');
            $table->decimal('cancelled_amount', 10, 2)->nullable()->after('voided_amount');
            $table->decimal('original_amount', 10, 2)->nullable()->after('cancelled_amount');
            
            // Add type and reference_transaction_id fields
            $table->string('type')->default('sale')->after('transaction_type');
            $table->unsignedBigInteger('reference_transaction_id')->nullable()->after('type');
            $table->foreign('reference_transaction_id')->references('id')->on('transactions')->onDelete('set null');
            
            // Add indexes for better performance
            $table->index('refunded_amount');
            $table->index('voided_amount');
            $table->index('cancelled_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['refunded_amount']);
            $table->dropIndex(['voided_amount']);
            $table->dropIndex(['cancelled_amount']);
            
            $table->dropColumn([
                'refunded_amount',
                'voided_amount',
                'cancelled_amount',
                'original_amount',
                'type',
                'reference_transaction_id',
            ]);
        });
    }
};
