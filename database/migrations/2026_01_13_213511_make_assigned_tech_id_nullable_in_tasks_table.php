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
        Schema::table('tasks', function (Blueprint $table) {
            // Drop the existing foreign key constraint first
            $table->dropForeign(['assigned_tech_id']);
            
            // Make the column nullable
            $table->foreignId('assigned_tech_id')
                ->nullable()
                ->change();
            
            // Re-add the foreign key constraint with nullOnDelete
            $table->foreign('assigned_tech_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['assigned_tech_id']);
            
            $table->foreignId('assigned_tech_id')
                ->nullable(false)
                ->change();
            
            $table->foreign('assigned_tech_id')
                ->references('id')
                ->on('users');
        });
    }
};
