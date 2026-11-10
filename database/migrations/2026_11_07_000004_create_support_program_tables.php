<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Support programs (DFD store D5): basic needs, mentorship, home visits,
 * repair projects, entrepreneurship, careers and alumni.
 */
return new class extends Migration
{
    public function up(): void
    {
        // distributions: basic-needs items handed out
        Schema::create('distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_category_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->date('given_on')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // mentors: mentors and counsellors (no login)
        Schema::create('mentors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // mentor_assignments: which students a mentor is assigned to
        Schema::create('mentor_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mentor_id')->constrained()->cascadeOnDelete();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

        // mentorship_sessions: each session, with restricted notes
        Schema::create('mentorship_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mentor_id')->nullable()->constrained()->nullOnDelete();
            $table->date('session_date')->nullable();
            $table->string('type')->default('mentorship'); // mentorship, counselling
            $table->text('notes')->nullable(); // restricted
            $table->boolean('follow_up_needed')->default(false);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // home_visits: household visit reports
        Schema::create('home_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->date('visit_date')->nullable();
            $table->text('living_conditions')->nullable();
            $table->text('income_sources')->nullable();
            $table->text('needs_identified')->nullable();
            $table->date('next_visit_due')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // repair_projects: home repairs with costs and status
        Schema::create('repair_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->decimal('actual_cost', 12, 2)->nullable();
            $table->string('status')->default('planned'); // planned, in_progress, completed
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // businesses: youth businesses
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('business_type')->nullable();
            $table->date('start_date')->nullable();
            $table->decimal('amount_given', 12, 2)->default(0);
            $table->string('funding_type')->default('grant'); // grant, loan
            $table->boolean('is_running')->default(true);
            $table->timestamps();
        });

        // business_checkins: progress check-ins
        Schema::create('business_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->date('checkin_date')->nullable();
            $table->decimal('monthly_income', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // loan_repayments: repayments for loan-funded businesses
        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('paid_on')->nullable();
            $table->timestamps();
        });

        // career_interests: each student's career interests
        Schema::create('career_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('interest');
            $table->timestamps();
        });

        // applications: applications Youthline helps with
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('university'); // university, college, scholarship, job
            $table->string('organisation')->nullable();
            $table->date('applied_on')->nullable();
            $table->string('status')->default('applied'); // applied, accepted, rejected
            $table->timestamps();
        });

        // alumni_outcomes: what graduates do afterwards
        Schema::create('alumni_outcomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('recorded_on')->nullable();
            // employed, further_study, business, seeking
            $table->string('outcome')->default('seeking');
            $table->text('details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni_outcomes');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('career_interests');
        Schema::dropIfExists('loan_repayments');
        Schema::dropIfExists('business_checkins');
        Schema::dropIfExists('businesses');
        Schema::dropIfExists('repair_projects');
        Schema::dropIfExists('home_visits');
        Schema::dropIfExists('mentorship_sessions');
        Schema::dropIfExists('mentor_assignments');
        Schema::dropIfExists('mentors');
        Schema::dropIfExists('distributions');
    }
};
