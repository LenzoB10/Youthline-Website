<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = 'activity_log';

        Schema::table($table, function (Blueprint $table) {
            $table->json('attribute_changes')->nullable()->after('causer_id');
        });

        DB::table($table)
            ->where(function ($query) {
                $query->whereNotNull('properties->attributes')
                    ->orWhereNotNull('properties->old');
            })
            ->orderBy('id')
            ->each(function ($row) use ($table) {
                $properties = json_decode($row->properties, true) ?? [];
                $changes = array_intersect_key($properties, array_flip(['attributes', 'old']));
                $remaining = array_diff_key($properties, array_flip(['attributes', 'old']));

                DB::table($table)->where('id', $row->id)->update([
                    'attribute_changes' => empty($changes) ? null : json_encode($changes),
                    'properties' => empty($remaining) ? null : json_encode($remaining),
                ]);
            });

        Schema::table($table, function (Blueprint $table) {
            $table->dropColumn('batch_uuid');
        });
    }

    public function down(): void
    {
        $table = 'activity_log';

        Schema::table($table, function (Blueprint $table) {
            $table->uuid('batch_uuid')->nullable()->after('properties');
            $table->dropColumn('attribute_changes');
        });
    }
};
