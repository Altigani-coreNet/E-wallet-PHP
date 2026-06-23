<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notifications')) {
            return;
        }

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->uuidMorphs('notifiable');
            // Primary searchable/filterable columns
            $table->string('topic')->nullable()->index();
            $table->string('target_type')->nullable()->index();
            $table->uuid('merchant_id')->nullable()->index();
            $table->uuid('user_id')->nullable()->index();
            $table->string('title')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('source')->nullable()->index();
            $table->boolean('is_admin')->default(false)->index();
            $table->uuid('notification_group_id')->nullable()->index();
            $table->string('notification_code')->nullable()->index();
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
