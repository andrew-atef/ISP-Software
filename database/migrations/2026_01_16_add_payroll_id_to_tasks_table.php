<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add payroll_id column to tasks table to link tasks to their payroll.
     * This is distinct from company_invoice_id. A task belongs to:
     * - ONE Payroll (Tech Payment)
     * - ONE Company Invoice (Customer Billing / Wire3)
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('payroll_id')
                ->nullable()
                ->after('tech_invoice_id')
                ->constrained('payrolls')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeignKeyIfExists(['payroll_id']);
            $table->dropColumn('payroll_id');
        });
    }
};
