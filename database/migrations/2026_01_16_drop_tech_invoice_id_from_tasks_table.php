<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Drop the legacy tech_invoice_id column from tasks table.
     * All functionality has been standardized to use payroll_id,
     * which follows Laravel naming conventions (foreign key to payrolls table).
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('tech_invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('tech_invoice_id')->nullable()->after('company_invoice_id');
        });
    }
};
