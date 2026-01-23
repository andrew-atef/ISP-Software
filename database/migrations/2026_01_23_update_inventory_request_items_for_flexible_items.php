<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if item_name column exists, if not add it
        if (!Schema::hasColumn('inventory_request_items', 'item_name')) {
            Schema::table('inventory_request_items', function (Blueprint $table) {
                $table->string('item_name')->nullable()->after('inventory_item_id');
            });
        }

        // Make inventory_item_id nullable using raw SQL to avoid constraint issues
        DB::statement('ALTER TABLE inventory_request_items MODIFY COLUMN inventory_item_id BIGINT UNSIGNED NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Make inventory_item_id NOT NULL again
        DB::statement('ALTER TABLE inventory_request_items MODIFY COLUMN inventory_item_id BIGINT UNSIGNED NOT NULL');

        // Drop the item_name column if it exists
        if (Schema::hasColumn('inventory_request_items', 'item_name')) {
            Schema::table('inventory_request_items', function (Blueprint $table) {
                $table->dropColumn('item_name');
            });
        }
    }
};



