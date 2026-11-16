<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 (Sponsors & reporting): sponsor notes, and the short progress update
 * a manager writes for each sponsored student — it goes into the sponsor report.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sponsors', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('phone');
        });

        Schema::table('sponsorships', function (Blueprint $table) {
            $table->text('progress_update')->nullable()->after('amount_per_year');
            $table->date('progress_update_on')->nullable()->after('progress_update');
            $table->foreignId('progress_update_by')->nullable()->after('progress_update_on')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sponsorships', function (Blueprint $table) {
            $table->dropConstrainedForeignId('progress_update_by');
            $table->dropColumn(['progress_update', 'progress_update_on']);
        });

        Schema::table('sponsors', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
