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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount_total', 10, 2);
            $table->integer('installments_count');
            $table->decimal('installment_amount', 10, 2);
            $table->string('status')->default('active'); // Enum
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('week_number');
            $table->integer('year');
            $table->decimal('gross_amount', 10, 2);
            $table->decimal('deductions_amount', 10, 2);
            $table->decimal('net_pay', 10, 2);
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('loan_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->boolean('is_paid')->default(false);
            $table->foreignId('payroll_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::dropIfExists('loan_installments');
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('loans');
    }
};
