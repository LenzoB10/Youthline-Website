<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Basic needs are mostly cash given to students to buy groceries: each record is
 * now a date and an amount. Older item-based records (item + quantity) are kept
 * as history, so the item is no longer required.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distributions', function (Blueprint $table) {
            $table->decimal('amount', 12, 2)->nullable()->after('item_category_id');
            $table->string('note')->nullable()->after('given_on');
            $table->foreignId('item_category_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('distributions', function (Blueprint $table) {
            $table->dropColumn(['amount', 'note']);
        });
    }
};
