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
        // First, drop the foreign key that depends on this index
        // Get the foreign key constraint name
        $constraints = DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS WHERE TABLE_NAME = 'inventory_request_items'");

        foreach ($constraints as $constraint) {
            try {
                DB::statement("ALTER TABLE `inventory_request_items` DROP FOREIGN KEY `{$constraint->CONSTRAINT_NAME}`");
            } catch (\Exception $e) {
                // Already dropped or doesn't exist
            }
        }

        // Now drop the unique index
        try {
            DB::statement("ALTER TABLE `inventory_request_items` DROP INDEX `ir_items_req_item_unique`");
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

