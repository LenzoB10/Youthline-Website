<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 (Settings & shared lists): current-term flag, an editable list of
 * exit reasons, and the Admin's approval decisions from plan section 2.1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('terms', function (Blueprint $table) {
            $table->boolean('is_current')->default(false)->after('end_date');
        });

        // exit_reasons: why a student leaves (students.exit_reason stores the chosen text)
        Schema::create('exit_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // system_settings: a single row holding approval limits and policy toggles
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('currency', 3)->default('MWK');
            $table->boolean('require_payment_approval')->default(true);
            $table->decimal('payment_approval_limit', 12, 2)->default(500000);
            $table->boolean('require_scholarship_approval')->default(true);
            $table->boolean('require_repair_approval')->default(true);
            $table->decimal('repair_approval_limit', 12, 2)->default(1000000);
            // author = only the manager who wrote a counselling note can read it
            $table->string('counselling_notes_visibility')->default('author');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('exit_reasons');

        Schema::table('terms', function (Blueprint $table) {
            $table->dropColumn('is_current');
        });
    }
};
