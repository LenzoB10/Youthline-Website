<?php

use App\Models\AcademicResult;
use App\Models\AcademicYear;
use App\Models\Distribution;
use App\Models\Enrollment;
use App\Models\ExitReason;
use App\Models\Institution;
use App\Models\ItemCategory;
use App\Models\Student;
use App\Models\SystemSetting;
use App\Models\Term;
use App\Models\User;
use App\Services\Settings\SystemSettingsService;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Permission::findOrCreate('update settings');
    Permission::findOrCreate('manage website content');

    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::findOrCreate('Admin')->givePermissionTo('update settings', 'manage website content'));

    // Managers edit website content but not system settings (permissions matrix).
    $this->manager = User::factory()->create();
    $this->manager->assignRole(Role::findOrCreate('Manager')->givePermissionTo('manage website content'));
});

function settingsStudent(): Student
{
    return Student::create(['first_name' => 'Test', 'last_name' => 'Student', 'status' => 'active']);
}

dataset('settings screens', [
    'admin.settings.index',
    'admin.settings.institutions.index',
    'admin.settings.years.index',
    'admin.settings.item-categories.index',
    'admin.settings.exit-reasons.index',
    'admin.settings.approvals.edit',
]);

it('renders every settings screen for the Admin', function (string $routeName) {
    actingAs($this->admin)->get(route($routeName))->assertOk();
})->with('settings screens');

it('keeps managers out of system settings', function (string $routeName) {
    actingAs($this->manager)->get(route($routeName))->assertForbidden();
})->with('settings screens');

it('falls back to a safe sort column instead of erroring', function () {
    Institution::create(['name' => 'B School', 'type' => 'secondary']);
    Institution::create(['name' => 'A School', 'type' => 'university']);

    actingAs($this->admin)
        ->get(route('admin.settings.institutions.index', ['sort_by' => 'password', 'sort_direction' => 'asc']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Settings/Institutions/Index')
            ->where('institutions.data.0.name', 'A School'));
});

// --- Institutions -----------------------------------------------------------

it('creates, validates and updates institutions', function () {
    actingAs($this->admin)
        ->post(route('admin.settings.institutions.store'), ['name' => 'Zomba Secondary', 'type' => 'secondary', 'district' => 'Zomba'])
        ->assertSessionHasNoErrors();

    $institution = Institution::first();
    expect($institution->type->value)->toBe('secondary');

    actingAs($this->admin)
        ->post(route('admin.settings.institutions.store'), ['name' => 'Zomba Secondary', 'type' => 'school'])
        ->assertSessionHasErrors(['name', 'type']);

    actingAs($this->admin)
        ->put(route('admin.settings.institutions.update', $institution), ['name' => 'Zomba Catholic Secondary', 'type' => 'secondary'])
        ->assertSessionHasNoErrors();

    expect($institution->fresh()->name)->toBe('Zomba Catholic Secondary');
});

it('logs changes to shared lists in the audit log', function () {
    actingAs($this->admin)->post(route('admin.settings.institutions.store'), ['name' => 'Logged School', 'type' => 'college']);

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => Institution::class,
        'subject_id' => Institution::first()->id,
        'causer_id' => $this->admin->id,
    ]);
});

it('refuses to delete an institution used in enrolment history', function () {
    $used = Institution::create(['name' => 'Used', 'type' => 'secondary']);
    $unused = Institution::create(['name' => 'Unused', 'type' => 'secondary']);
    $enrollment = Enrollment::create(['student_id' => settingsStudent()->id, 'institution_id' => $used->id, 'level' => 'form_1']);

    actingAs($this->admin)->delete(route('admin.settings.institutions.destroy', $used))->assertSessionHas('error');
    actingAs($this->admin)->delete(route('admin.settings.institutions.destroy', $unused))->assertSessionHas('success');

    expect(Institution::query()->pluck('name')->all())->toBe(['Used'])
        ->and($enrollment->fresh()->institution_id)->toBe($used->id);
});

// --- Academic calendar ------------------------------------------------------

it('makes the first academic year current automatically', function () {
    actingAs($this->admin)->post(route('admin.settings.years.store'), ['name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
    actingAs($this->admin)->post(route('admin.settings.years.store'), ['name' => '2027']);

    expect(AcademicYear::query()->where('is_current', true)->pluck('name')->all())->toBe(['2026']);

    actingAs($this->admin)
        ->post(route('admin.settings.years.store'), ['name' => '2027'])
        ->assertSessionHasErrors('name');
    actingAs($this->admin)
        ->post(route('admin.settings.years.store'), ['name' => '2028', 'start_date' => '2028-12-01', 'end_date' => '2028-01-01'])
        ->assertSessionHasErrors('end_date');
});

it('keeps exactly one current year and moves the current term with it', function () {
    $old = AcademicYear::create(['name' => '2025', 'is_current' => true]);
    $oldTerm = Term::create(['academic_year_id' => $old->id, 'name' => 'Term 3', 'is_current' => true]);
    $new = AcademicYear::create(['name' => '2026']);

    actingAs($this->admin)->put(route('admin.settings.years.current', $new))->assertSessionHas('success');

    expect(AcademicYear::query()->where('is_current', true)->pluck('id')->all())->toBe([$new->id])
        ->and($oldTerm->fresh()->is_current)->toBeFalse();
});

it('makes a term current along with its year', function () {
    $current = AcademicYear::create(['name' => '2025', 'is_current' => true]);
    Term::create(['academic_year_id' => $current->id, 'name' => 'Term 3', 'is_current' => true]);
    $next = AcademicYear::create(['name' => '2026']);
    $term = Term::create(['academic_year_id' => $next->id, 'name' => 'Term 1']);

    actingAs($this->admin)->put(route('admin.settings.terms.current', $term))->assertSessionHas('success');

    expect($next->fresh()->is_current)->toBeTrue()
        ->and($current->fresh()->is_current)->toBeFalse()
        ->and(Term::query()->where('is_current', true)->pluck('id')->all())->toBe([$term->id]);
});

it('validates term names and dates against their year', function () {
    $year = AcademicYear::create(['name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
    $other = AcademicYear::create(['name' => '2027']);
    Term::create(['academic_year_id' => $year->id, 'name' => 'Term 1']);

    actingAs($this->admin)
        ->post(route('admin.settings.terms.store'), ['academic_year_id' => $year->id, 'name' => 'Term 1'])
        ->assertSessionHasErrors('name');

    actingAs($this->admin)
        ->post(route('admin.settings.terms.store'), ['academic_year_id' => $other->id, 'name' => 'Term 1'])
        ->assertSessionHasNoErrors();

    actingAs($this->admin)
        ->post(route('admin.settings.terms.store'), ['academic_year_id' => $year->id, 'name' => 'Term 2', 'start_date' => '2025-12-01'])
        ->assertSessionHasErrors('start_date');
});

it('protects the current period and anything holding records from deletion', function () {
    $current = AcademicYear::create(['name' => '2025', 'is_current' => true]);
    $currentTerm = Term::create(['academic_year_id' => $current->id, 'name' => 'Term 1', 'is_current' => true]);

    $past = AcademicYear::create(['name' => '2024']);
    $termWithResults = Term::create(['academic_year_id' => $past->id, 'name' => 'Term 1']);
    AcademicResult::create(['student_id' => settingsStudent()->id, 'term_id' => $termWithResults->id, 'exam_type' => 'term']);

    $empty = AcademicYear::create(['name' => '2023']);
    Term::create(['academic_year_id' => $empty->id, 'name' => 'Term 1']);

    actingAs($this->admin)->delete(route('admin.settings.years.destroy', $current))->assertSessionHas('error');
    actingAs($this->admin)->delete(route('admin.settings.terms.destroy', $currentTerm))->assertSessionHas('error');
    actingAs($this->admin)->delete(route('admin.settings.years.destroy', $past))->assertSessionHas('error');
    actingAs($this->admin)->delete(route('admin.settings.terms.destroy', $termWithResults))->assertSessionHas('error');
    actingAs($this->admin)->delete(route('admin.settings.years.destroy', $empty))->assertSessionHas('success');

    expect(AcademicYear::query()->pluck('name')->sort()->values()->all())->toBe(['2024', '2025'])
        ->and(Term::query()->count())->toBe(2);
});

// --- Item categories --------------------------------------------------------

it('manages item categories and protects distributed ones', function () {
    actingAs($this->admin)
        ->post(route('admin.settings.item-categories.store'), ['name' => 'Uniforms', 'unit_cost' => 8500])
        ->assertSessionHasNoErrors();
    actingAs($this->admin)
        ->post(route('admin.settings.item-categories.store'), ['name' => 'Bags', 'unit_cost' => -1])
        ->assertSessionHasErrors('unit_cost');

    $uniforms = ItemCategory::first();
    $distribution = Distribution::create(['student_id' => settingsStudent()->id, 'item_category_id' => $uniforms->id]);
    $books = ItemCategory::create(['name' => 'Books']);

    actingAs($this->admin)->delete(route('admin.settings.item-categories.destroy', $uniforms))->assertSessionHas('error');
    actingAs($this->admin)->delete(route('admin.settings.item-categories.destroy', $books))->assertSessionHas('success');

    expect($distribution->fresh())->not->toBeNull()
        ->and(ItemCategory::query()->pluck('name')->all())->toBe(['Uniforms']);
});

// --- Exit reasons -----------------------------------------------------------

it('locks the name of exit reasons already on student records', function () {
    $used = ExitReason::create(['name' => 'Family relocated']);
    $unused = ExitReason::create(['name' => 'Typo reason']);
    Student::create(['first_name' => 'A', 'last_name' => 'B', 'status' => 'dropped_out', 'exit_reason' => 'Family relocated']);

    actingAs($this->admin)
        ->put(route('admin.settings.exit-reasons.update', $used), ['name' => 'Moved away', 'is_active' => true])
        ->assertSessionHasErrors('name');

    actingAs($this->admin)
        ->put(route('admin.settings.exit-reasons.update', $used), ['name' => 'Family relocated', 'is_active' => false])
        ->assertSessionHasNoErrors();

    actingAs($this->admin)
        ->put(route('admin.settings.exit-reasons.update', $unused), ['name' => 'Fixed reason', 'is_active' => true])
        ->assertSessionHasNoErrors();

    actingAs($this->admin)->delete(route('admin.settings.exit-reasons.destroy', $used))->assertSessionHas('error');

    expect($used->fresh()->is_active)->toBeFalse()
        ->and($unused->fresh()->name)->toBe('Fixed reason')
        ->and(ExitReason::query()->active()->pluck('name')->all())->toBe(['Fixed reason']);
});

// --- Approvals --------------------------------------------------------------

it('saves approval settings, records who changed them, and applies them', function () {
    $service = app(SystemSettingsService::class);
    expect($service->scholarshipNeedsApproval())->toBeTrue();

    actingAs($this->admin)
        ->put(route('admin.settings.approvals.update'), [
            'currency' => 'MWK',
            'require_scholarship_approval' => false,
            'counselling_notes_visibility' => 'author',
        ])
        ->assertSessionHasNoErrors();

    expect(SystemSetting::first()->updated_by)->toBe($this->admin->id)
        ->and($service->scholarshipNeedsApproval())->toBeFalse();
});

it('validates approval settings', function () {
    actingAs($this->admin)
        ->put(route('admin.settings.approvals.update'), [
            'currency' => 'kwacha',
            'require_scholarship_approval' => 'maybe',
            'counselling_notes_visibility' => 'everyone',
        ])
        ->assertSessionHasErrors(['currency', 'require_scholarship_approval', 'counselling_notes_visibility']);
});
