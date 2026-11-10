<?php

use App\Models\CoreValue;
use App\Models\Program;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Permission::findOrCreate('manage website content');
    $role = Role::findOrCreate('Editor');
    $role->givePermissionTo('manage website content');

    $this->editor = User::factory()->create();
    $this->editor->assignRole($role);

    $this->outsider = User::factory()->create();
});

it('blocks users without permission from program management', function () {
    actingAs($this->outsider)->get(route('admin.website.programs.index'))->assertForbidden();
});

it('renders the content editor screens', function (string $routeName) {
    actingAs($this->editor)->get(route($routeName))->assertOk();
})->with([
    'admin.website.programs.index',
    'admin.website.programs.create',
    'admin.website.values.index',
    'admin.website.team.index',
    'admin.website.team.create',
]);

it('creates a program with a unique slug and an uploaded image', function () {
    Storage::fake('public');

    actingAs($this->editor)
        ->post(route('admin.website.programs.store'), [
            'name' => 'Education Support',
            'summary' => 'Scholarships',
            'image' => UploadedFile::fake()->image('program.jpg'),
        ])
        ->assertRedirect(route('admin.website.programs.index'));

    $program = Program::first();
    expect($program->name)->toBe('Education Support')
        ->and($program->slug)->toBe('education-support')
        ->and($program->image)->toContain('/storage/programs/');

    Storage::disk('public')->assertExists('programs/'.basename($program->image));
});

it('generates distinct slugs for programs with the same name', function () {
    actingAs($this->editor)->post(route('admin.website.programs.store'), ['name' => 'Careers']);
    actingAs($this->editor)->post(route('admin.website.programs.store'), ['name' => 'Careers']);

    expect(Program::pluck('slug')->all())->toBe(['careers', 'careers-2']);
});

it('updates and deletes a program', function () {
    $program = Program::create(['name' => 'Old', 'slug' => 'old']);

    actingAs($this->editor)
        ->put(route('admin.website.programs.update', $program), ['name' => 'New name'])
        ->assertRedirect(route('admin.website.programs.index'));
    expect($program->fresh()->name)->toBe('New name');

    actingAs($this->editor)->delete(route('admin.website.programs.destroy', $program))->assertRedirect();
    expect(Program::count())->toBe(0);
});

it('manages core values', function () {
    actingAs($this->editor)
        ->post(route('admin.website.values.store'), ['name' => 'Compassion', 'description' => 'Kindness'])
        ->assertRedirect();

    $value = CoreValue::first();
    expect($value->name)->toBe('Compassion');

    actingAs($this->editor)->put(route('admin.website.values.update', $value), ['name' => 'Empathy']);
    expect($value->fresh()->name)->toBe('Empathy');

    actingAs($this->editor)->delete(route('admin.website.values.destroy', $value));
    expect(CoreValue::count())->toBe(0);
});

it('creates a team member with a photo', function () {
    Storage::fake('public');

    actingAs($this->editor)
        ->post(route('admin.website.team.store'), [
            'name' => 'Jane Leader',
            'position' => 'Director',
            'photo' => UploadedFile::fake()->image('jane.png'),
        ])
        ->assertRedirect(route('admin.website.team.index'));

    $member = TeamMember::first();
    expect($member->name)->toBe('Jane Leader')
        ->and($member->photo)->toContain('/storage/team/');

    Storage::disk('public')->assertExists('team/'.basename($member->photo));
});

it('validates program image uploads', function () {
    actingAs($this->editor)
        ->post(route('admin.website.programs.store'), [
            'name' => 'Bad image',
            'image' => UploadedFile::fake()->create('notanimage.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors('image');

    expect(Program::count())->toBe(0);
});
