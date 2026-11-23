<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Home repairs become "family support": a simple record of support already given
 * to a family (money, food, household items, a home repair, ...). There are no
 * planning stages or approvals any more.
 *
 * Existing repairs are carried over as "Home repair" entries, photos included.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_supports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->date('given_on');
            $table->string('type', 30); // App\Enums\FamilySupportType
            $table->string('description');
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['household_id', 'given_on']);
        });

        if (Schema::hasTable('repair_projects')) {
            DB::table('repair_projects')->orderBy('id')->get()->each(function ($repair) {
                $completed = $repair->status === 'completed';

                $id = DB::table('family_supports')->insertGetId([
                    'household_id' => $repair->household_id,
                    'given_on' => $repair->completed_on ?? $repair->started_on ?? substr((string) $repair->created_at, 0, 10) ?: now()->toDateString(),
                    'type' => 'home_repair',
                    'description' => str($repair->description)->limit(250, '')->toString(),
                    'amount' => $repair->actual_cost ?? $repair->estimated_cost,
                    'note' => $completed ? null : 'Carried over from a repair that was not marked completed.',
                    'created_by' => $repair->created_by,
                    'created_at' => $repair->created_at,
                    'updated_at' => $repair->updated_at,
                ]);

                DB::table('photos')
                    ->where('photoable_type', 'App\\Models\\RepairProject')
                    ->where('photoable_id', $repair->id)
                    ->update(['photoable_type' => 'App\\Models\\FamilySupport', 'photoable_id' => $id, 'kind' => 'support']);
            });

            Schema::drop('repair_projects');
        }

        // Repair approvals are gone.
        DB::table('notifications')->where('type', 'App\\Notifications\\RepairAwaitingApproval')->delete();

        if (Schema::hasColumn('system_settings', 'require_repair_approval')) {
            Schema::table('system_settings', function (Blueprint $table) {
                $table->dropColumn(['require_repair_approval', 'repair_approval_limit']);
            });
        }

        $permission = DB::table('permissions')->where('name', 'approve repairs')->value('id');
        if ($permission) {
            DB::table('role_has_permissions')->where('permission_id', $permission)->delete();
            DB::table('model_has_permissions')->where('permission_id', $permission)->delete();
            DB::table('permissions')->where('id', $permission)->delete();
        }
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->boolean('require_repair_approval')->default(true);
            $table->decimal('repair_approval_limit', 12, 2)->default(1000000);
        });

        Schema::create('repair_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->decimal('actual_cost', 12, 2)->nullable();
            $table->string('status')->default('planned');
            $table->string('approval_status')->default('approved');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->date('started_on')->nullable();
            $table->date('completed_on')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::dropIfExists('family_supports');
    }
};
