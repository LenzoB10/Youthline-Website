<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The public "Our Impact" page was removed, so its editable text is no longer needed.
 * (The impact figures themselves still show on the Home and About pages.)
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('pages')->where('key', 'impact')->delete();
    }

    public function down(): void
    {
        // The page no longer exists; nothing to restore.
    }
};
