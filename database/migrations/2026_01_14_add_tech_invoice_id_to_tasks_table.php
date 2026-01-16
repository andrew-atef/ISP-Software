<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add tech_invoice_id column to track which tech invoice (payroll invoice)
     * this task's tech_price has been assigned to.
     *
     * This is currently a simple unsignedBigInteger for future extensibility.
     * If a tech_invoices table is created later, add a foreign key constraint.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('tech_invoice_id')->nullable()->after('company_invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('tech_invoice_id');
        });
    }
};
