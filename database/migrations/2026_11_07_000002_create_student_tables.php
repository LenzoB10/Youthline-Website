<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Student core (DFD store D2): students, enrolments per year, programs joined.
 */
return new class extends Migration
{
    public function up(): void
    {
        // students: each young person, guardian details and status
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable(); // male, female, other
            $table->string('phone')->nullable();
            $table->string('home_location')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('guardian_phone')->nullable();
            $table->string('guardian_relationship')->nullable();
            // applicant, active, graduated, dropped_out, transferred, paused
            $table->string('status')->default('applicant');
            $table->string('exit_reason')->nullable();
            $table->date('exit_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // enrollments: one row per student per academic year (keeps full history)
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('institution_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();
            // form_1..form_4, year_1..year_5
            $table->string('level')->nullable();
            $table->string('course')->nullable();
            $table->boolean('is_repeating')->default(false);
            $table->timestamps();
        });

        // student_programs: which students are in each program
        Schema::create('student_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_programs');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('students');
    }
};
