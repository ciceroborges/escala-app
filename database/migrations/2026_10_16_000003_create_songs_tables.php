<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('song_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ministry_id')->constrained()->cascadeOnDelete();
            $table->string('level', 20);
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['ministry_id', 'level']);
        });

        Schema::create('song_category_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('song_category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('emoji', 16)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('songs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ministry_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('artist')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('chords_url')->nullable();
            $table->foreignId('primary_option_id')->nullable()->constrained('song_category_options')->nullOnDelete();
            $table->foreignId('secondary_option_id')->nullable()->constrained('song_category_options')->nullOnDelete();
            $table->timestamps();

            $table->index(['ministry_id', 'title']);
        });

        // tom é de música + cantor; "starting_key" cobre o "G, mas começa em C"
        Schema::create('song_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('song_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('musical_key', 4)->nullable();
            $table->string('starting_key', 4)->nullable();
            $table->timestamps();

            $table->unique(['song_id', 'member_id']);
        });

        Schema::create('setlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worship_service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('song_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('setlist_item_singers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('setlist_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('musical_key', 4)->nullable();
            $table->string('starting_key', 4)->nullable();
            $table->timestamps();

            $table->unique(['setlist_item_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setlist_item_singers');
        Schema::dropIfExists('setlist_items');
        Schema::dropIfExists('song_keys');
        Schema::dropIfExists('songs');
        Schema::dropIfExists('song_category_options');
        Schema::dropIfExists('song_categories');
    }
};
