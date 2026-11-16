<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 (Academics & Fees): what the original tables were missing for
 * MSCE points, scholarship approval and the payment approval / error trail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_results', function (Blueprint $table) {
            // MSCE is graded by aggregate points (best six subjects, 6 = best), not a % average.
            $table->unsignedSmallInteger('msce_points')->nullable()->after('average');
            $table->text('remarks')->nullable()->after('position');
        });

        Schema::table('fees', function (Blueprint $table) {
            // A new scholarship can need Admin sign-off before any payment is made.
            $table->string('approval_status')->default('approved')->after('youthline_share'); // approved, pending, rejected
            $table->foreignId('approved_by')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->string('rejection_reason')->nullable()->after('approved_at');
            $table->string('notes')->nullable()->after('rejection_reason');
            $table->foreignId('created_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->string('rejection_reason')->nullable()->after('approved_at');
            // Payments are never deleted: a wrong entry is marked as an error, with a reason.
            $table->string('error_reason')->nullable()->after('rejection_reason');
            $table->foreignId('error_marked_by')->nullable()->after('error_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('error_marked_at')->nullable()->after('error_marked_by');
        });

        Schema::table('system_settings', function (Blueprint $table) {
            // Alert when a term average falls by at least this many percentage points.
            $table->decimal('grade_drop_threshold', 5, 2)->default(10)->after('repair_approval_limit');
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn('grade_drop_threshold');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('error_marked_by');
            $table->dropColumn(['approved_at', 'rejection_reason', 'error_reason', 'error_marked_at']);
        });

        Schema::table('fees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['approval_status', 'approved_at', 'rejection_reason', 'notes']);
        });

        Schema::table('academic_results', function (Blueprint $table) {
            $table->dropColumn(['msce_points', 'remarks']);
        });
    }
};
