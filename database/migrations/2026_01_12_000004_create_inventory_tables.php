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
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique();
            $table->string('type'); // Enum: indoor, outdoor, tool
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'inventory_item_id']);
        });

        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained();
            $table->foreignId('source_user_id')->nullable()->constrained('users');
            $table->foreignId('target_user_id')->nullable()->constrained('users');
            $table->foreignId('task_id')->nullable()->constrained();
            $table->integer('quantity');
            $table->string('type'); // Enum: restock, transfer, consumed, return
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('inventory_wallets');
        Schema::dropIfExists('inventory_items');
    }
};
