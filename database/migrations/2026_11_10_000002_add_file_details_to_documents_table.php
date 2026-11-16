<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2: student documents are real uploads on the private disk, so keep the
 * original file name, type and size alongside the stored path.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('original_name')->nullable()->after('file_path');
            $table->string('mime_type')->nullable()->after('original_name');
            $table->unsignedBigInteger('size')->nullable()->after('mime_type');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['original_name', 'mime_type', 'size']);
        });
    }
};
