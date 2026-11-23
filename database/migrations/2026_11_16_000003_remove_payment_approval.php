<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Payments no longer need approval: anything still waiting counts as paid, and
 * the approval switch and limit are removed from the system settings.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('payments')->where('status', 'pending_approval')->update(['status' => 'paid']);

        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn(['require_payment_approval', 'payment_approval_limit']);
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->boolean('require_payment_approval')->default(false)->after('currency');
            $table->decimal('payment_approval_limit', 14, 2)->default(500000)->after('require_payment_approval');
        });
    }
};
