<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ministry_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->string('status', 20)->default('draft');
            $table->date('availability_deadline')->nullable();
            $table->timestamps();

            $table->unique(['ministry_id', 'year', 'month']);
        });

        Schema::create('worship_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['schedule_id', 'date']);
        });

        Schema::create('unavailabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('worship_service_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['member_id', 'worship_service_id']);
        });

        Schema::create('availability_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['member_id', 'schedule_id']);
        });

        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worship_service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('position_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['worship_service_id', 'member_id', 'position_id'], 'assignments_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('availability_responses');
        Schema::dropIfExists('unavailabilities');
        Schema::dropIfExists('worship_services');
        Schema::dropIfExists('schedules');
    }
};
