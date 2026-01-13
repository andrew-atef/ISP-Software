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
        Schema::create('original_techs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // e.g., XC5
            $table->timestamps();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('original_tech_code');
            $table->foreignId('original_tech_id')->nullable()->constrained('original_techs')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['original_tech_id']);
            $table->dropColumn('original_tech_id');
            $table->string('original_tech_code')->nullable();
        });

        Schema::dropIfExists('original_techs');
    }
};
