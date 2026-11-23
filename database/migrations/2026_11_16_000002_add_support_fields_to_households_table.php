<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Families are supported in their own right, not only as a student's home:
 * a family can be added directly, be "currently supported" or not, and give
 * its own consent for home photos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('households', function (Blueprint $table) {
            $table->boolean('is_supported')->default(true)->after('members_count');
            $table->text('notes')->nullable()->after('is_supported');
            $table->boolean('photo_consent')->default(false)->after('notes');
            $table->date('photo_consent_on')->nullable()->after('photo_consent');
            $table->foreignId('created_by')->nullable()->after('photo_consent_on')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('households', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['is_supported', 'notes', 'photo_consent', 'photo_consent_on']);
        });
    }
};
