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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->string('original_tech_code')->nullable();
            $table->foreignId('assigned_tech_id')->constrained('users');
            $table->string('task_type'); // Enum: new_install, drop_bury, service_call, site_survey
            $table->string('status')->default('pending'); // Enum
            $table->string('financial_status')->default('not_billable'); // Enum
            $table->decimal('company_price', 10, 2)->default(0);
            $table->decimal('tech_price', 10, 2)->default(0);
            $table->date('scheduled_date')->nullable();
            $table->time('time_slot_start')->nullable();
            $table->time('time_slot_end')->nullable();
            $table->string('saf_link')->nullable();
            $table->text('description')->nullable();
            $table->string('import_batch_id')->nullable();
            $table->timestamp('completion_date')->nullable();
            $table->boolean('is_offline_sync')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('task_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->string('ont_serial')->nullable();
            $table->string('eero_serial_1')->nullable();
            $table->string('eero_serial_2')->nullable();
            $table->string('eero_serial_3')->nullable();
            $table->boolean('drop_bury_status')->default(false);
            $table->boolean('sidewalk_bore_status')->default(false);
            $table->dateTime('start_time_actual')->nullable();
            $table->dateTime('end_time_actual')->nullable();
            $table->text('tech_notes')->nullable();
            $table->decimal('start_lat', 10, 8)->nullable();
            $table->decimal('start_lng', 11, 8)->nullable();
            $table->decimal('end_lat', 10, 8)->nullable();
            $table->decimal('end_lng', 11, 8)->nullable();
            $table->timestamps();
        });

        Schema::create('task_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('type'); // Enum: general, bury, bore, pigtail
            $table->json('watermark_data')->nullable(); // Lat/Lng/Time
            $table->timestamp('taken_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_media');
        Schema::dropIfExists('task_details');
        Schema::dropIfExists('tasks');
    }
};
