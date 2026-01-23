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
        // Drop foreign key constraint first by name (usually inventory_request_items_inventory_item_id_foreign)
        try {
            DB::statement('ALTER TABLE inventory_request_items DROP FOREIGN KEY inventory_request_items_inventory_item_id_foreign');
        } catch (\Exception $e) {
            // Try alternative name
            try {
                DB::statement("ALTER TABLE inventory_request_items DROP FOREIGN KEY `inventory_request_items_inventory_item_id_foreign`");
            } catch (\Exception $e2) {
                // Constraint might already be gone
            }
        }

        // Drop the unique constraint
        try {
            DB::statement('ALTER TABLE inventory_request_items DROP INDEX ir_items_req_item_unique');
        } catch (\Exception $e) {
            // Already dropped
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Skip reverse
    }
};


