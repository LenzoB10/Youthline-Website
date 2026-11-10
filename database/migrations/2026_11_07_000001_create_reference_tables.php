<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reference / shared-list tables (DFD store D1) and households.
 * These have no foreign keys to other Youthline tables and are created first.
 */
return new class extends Migration
{
    public function up(): void
    {
        // institutions: secondary schools, colleges and universities
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('secondary'); // secondary, college, university
            $table->string('district')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        // academic_years: the school calendar
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. 2025
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        // terms: one academic year has many terms
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Term 1, Term 2, Term 3
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

        // programs: the six core programs (shared by system and website)
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        // item_categories: basic-needs items
        Schema::create('item_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->timestamps();
        });

        // households: a family or home
        Schema::create('households', function (Blueprint $table) {
            $table->id();
            $table->string('head_name')->nullable();
            $table->string('village_area')->nullable();
            $table->string('district')->nullable();
            $table->string('phone')->nullable();
            $table->unsignedInteger('members_count')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('households');
        Schema::dropIfExists('item_categories');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('terms');
        Schema::dropIfExists('academic_years');
        Schema::dropIfExists('institutions');
    }
};
