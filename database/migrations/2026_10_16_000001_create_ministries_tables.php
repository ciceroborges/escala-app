<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ministries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('church_name')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ministry_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('emoji', 16)->nullable();
            $table->boolean('uses_microphone')->default(false);
            $table->boolean('is_vocal')->default(false);
            $table->unsignedTinyInteger('min_per_service')->default(0);
            $table->unsignedTinyInteger('max_per_service')->default(1);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ministry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 20)->nullable();
            $table->string('role', 20)->default('member');
            $table->json('permissions')->nullable();
            // null = sem limite
            $table->unsignedTinyInteger('monthly_limit')->nullable()->default(2);
            // escalado em todo culto nesta função (ex.: líder, único tecladista)
            $table->foreignId('fixed_position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['ministry_id', 'email']);
        });

        Schema::create('member_position', function (Blueprint $table) {
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('position_id')->constrained()->cascadeOnDelete();
            $table->primary(['member_id', 'position_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_position');
        Schema::dropIfExists('members');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('ministries');
    }
};
