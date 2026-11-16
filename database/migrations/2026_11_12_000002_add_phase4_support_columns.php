<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 (Support Programs): repair approvals and dates, check-in status,
 * application details, private photos, and the reminder intervals.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_visits', function (Blueprint $table) {
            // How many people lived in the home at this visit (households.members_count is the latest).
            $table->unsignedInteger('members_count')->nullable()->after('visit_date');
        });

        Schema::table('repair_projects', function (Blueprint $table) {
            $table->string('approval_status')->default('approved')->after('status'); // approved, pending, rejected
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->string('rejection_reason')->nullable()->after('approved_at');
            $table->date('started_on')->nullable()->after('rejection_reason');
            $table->date('completed_on')->nullable()->after('started_on');
            $table->foreignId('created_by')->nullable()->after('completed_on')->constrained('users')->nullOnDelete();
        });

        Schema::table('business_checkins', function (Blueprint $table) {
            $table->boolean('is_running')->default(true)->after('checkin_date');
        });

        Schema::table('loan_repayments', function (Blueprint $table) {
            $table->foreignId('recorded_by')->nullable()->after('paid_on')->constrained('users')->nullOnDelete();
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->string('course')->nullable()->after('organisation');
            $table->string('notes')->nullable()->after('status');
        });

        // Photos of home visits and repairs, kept on the PRIVATE disk.
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->morphs('photoable'); // home_visits, repair_projects
            $table->string('kind')->default('visit'); // visit, before, after
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('system_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('mentorship_overdue_days')->default(60)->after('grade_drop_threshold');
            $table->unsignedSmallInteger('home_visit_interval_months')->default(12)->after('mentorship_overdue_days');
            $table->unsignedSmallInteger('business_checkin_days')->default(90)->after('home_visit_interval_months');
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn(['mentorship_overdue_days', 'home_visit_interval_months', 'business_checkin_days']);
        });
        Schema::dropIfExists('photos');
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['course', 'notes']);
        });
        Schema::table('loan_repayments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recorded_by');
        });
        Schema::table('business_checkins', function (Blueprint $table) {
            $table->dropColumn('is_running');
        });
        Schema::table('repair_projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['approval_status', 'approved_at', 'rejection_reason', 'started_on', 'completed_on']);
        });
        Schema::table('home_visits', function (Blueprint $table) {
            $table->dropColumn('members_count');
        });
    }
};
