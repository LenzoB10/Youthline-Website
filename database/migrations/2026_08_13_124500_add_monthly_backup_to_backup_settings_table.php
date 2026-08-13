<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_settings', function (Blueprint $table) {
            $table->boolean('monthly_backup_enabled')->default(true)->after('backup_retention_days');
            $table->unsignedInteger('monthly_backup_retention_months')->default(12)->after('monthly_backup_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('backup_settings', function (Blueprint $table) {
            $table->dropColumn(['monthly_backup_enabled', 'monthly_backup_retention_months']);
        });
    }
};
