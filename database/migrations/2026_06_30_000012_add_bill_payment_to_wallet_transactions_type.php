<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TYPES = ['topup', 'payment', 'transfer', 'refund', 'adjustment', 'bill_payment'];

    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            $values = implode("','", self::TYPES);
            DB::statement("ALTER TABLE wallet_transactions MODIFY COLUMN type ENUM('{$values}') NOT NULL");

            return;
        }

        // PHPUnit (SQLite): recreate table so bill_payment is allowed in tests.
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('wallet_transactions');
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('wallet_id');
            $table->enum('type', self::TYPES);
            $table->enum('direction', ['credit', 'debit']);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2)->default('0.00');
            $table->string('reference')->nullable();
            $table->string('reference_id', 36)->nullable();
            $table->text('description')->nullable();
            $table->text('note')->nullable();
            $table->integer('created_by')->default(0);
            $table->timestamps();

            $table->foreign('wallet_id')->references('id')->on('wallets')->cascadeOnDelete();
            $table->index(['reference', 'reference_id']);
        });
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE wallet_transactions MODIFY COLUMN type ENUM('topup', 'payment', 'transfer', 'refund', 'adjustment') NOT NULL");

            return;
        }

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('wallet_transactions');
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('wallet_id');
            $table->enum('type', ['topup', 'payment', 'transfer', 'refund', 'adjustment']);
            $table->enum('direction', ['credit', 'debit']);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2)->default('0.00');
            $table->string('reference')->nullable();
            $table->string('reference_id', 36)->nullable();
            $table->text('description')->nullable();
            $table->text('note')->nullable();
            $table->integer('created_by')->default(0);
            $table->timestamps();

            $table->foreign('wallet_id')->references('id')->on('wallets')->cascadeOnDelete();
            $table->index(['reference', 'reference_id']);
        });
        Schema::enableForeignKeyConstraints();
    }
};
