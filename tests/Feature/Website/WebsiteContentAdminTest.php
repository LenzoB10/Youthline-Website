<?php

use App\Models\CoreValue;
use App\Models\Partner;
use App\Models\Program;
use App\Models\TeamMember;
use App\Models\Testimonial;
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

it('only accepts JPG, PNG or WebP program photos', function () {
    Storage::fake('public');

    actingAs($this->editor)
        ->post(route('admin.website.programs.store'), [
            'name' => 'Careers',
            'image' => UploadedFile::fake()->image('photo.gif'),
        ])
        ->assertSessionHasErrors('image');

    actingAs($this->editor)
        ->post(route('admin.website.programs.store'), [
            'name' => 'Careers',
            'image' => UploadedFile::fake()->image('photo.webp'),
        ])
        ->assertSessionHasNoErrors();
});

it('links each program to its public page from the admin list', function () {
    Program::create(['name' => 'Careers', 'slug' => 'careers']);

    actingAs($this->editor)
        ->get(route('admin.website.programs.index'))
        ->assertInertia(fn ($page) => $page->where('programs.0.slug', 'careers'));
});

// --- Testimonials -----------------------------------------------------------

it('manages testimonials and shows only published ones on the home page', function () {
    Storage::fake('public');
    actingAs($this->outsider)->get(route('admin.website.testimonials.index'))->assertForbidden();

    actingAs($this->editor)->get(route('admin.website.testimonials.create'))->assertOk();
    actingAs($this->editor)->post(route('admin.website.testimonials.store'), [
        'name' => 'Grace M.', 'role' => 'Form 4 student', 'quote' => 'Youthline kept me in school.',
        'is_published' => 1, 'display_order' => 1, 'photo' => UploadedFile::fake()->image('grace.jpg'),
    ])->assertRedirect(route('admin.website.testimonials.index'));
    actingAs($this->editor)->post(route('admin.website.testimonials.store'), [
        'name' => 'Hidden person', 'quote' => 'Not ready yet.', 'is_published' => 0,
    ])->assertSessionHasNoErrors();

    $grace = Testimonial::query()->where('name', 'Grace M.')->sole();
    expect($grace->photo)->not->toBeNull();

    $this->get(route('home'))->assertInertia(fn ($page) => $page
        ->has('testimonials', 1)
        ->where('testimonials.0.name', 'Grace M.')
        ->where('testimonials.0.role', 'Form 4 student'));

    actingAs($this->editor)->post(route('admin.website.testimonials.update', $grace), [
        '_method' => 'put', 'name' => 'Grace M.', 'quote' => 'Updated quote.', 'is_published' => 0,
    ])->assertSessionHasNoErrors();
    $this->get(route('home'))->assertInertia(fn ($page) => $page->has('testimonials', 0));

    actingAs($this->editor)->post(route('admin.website.testimonials.store'), ['name' => '', 'quote' => str_repeat('a', 601), 'is_published' => 1])
        ->assertSessionHasErrors(['name', 'quote']);

    actingAs($this->editor)->delete(route('admin.website.testimonials.destroy', $grace))->assertSessionHas('success');
    expect(Testimonial::query()->count())->toBe(1);
});

// --- Partners ---------------------------------------------------------------

it('manages partners and shows only published ones in the home page logo strip', function () {
    Storage::fake('public');
    actingAs($this->outsider)->get(route('admin.website.partners.index'))->assertForbidden();

    actingAs($this->editor)->get(route('admin.website.partners.create'))->assertOk();
    actingAs($this->editor)->post(route('admin.website.partners.store'), [
        'name' => 'Blantyre Rotary Club', 'website_url' => 'https://example.org', 'is_published' => 1,
        'display_order' => 1, 'logo' => UploadedFile::fake()->image('logo.png'),
    ])->assertRedirect(route('admin.website.partners.index'));
    actingAs($this->editor)->post(route('admin.website.partners.store'), ['name' => 'Not yet agreed', 'is_published' => 0])
        ->assertSessionHasNoErrors();

    $this->get(route('home'))->assertInertia(fn ($page) => $page
        ->has('partners', 1)
        ->where('partners.0.name', 'Blantyre Rotary Club')
        ->where('partners.0.url', 'https://example.org'));

    actingAs($this->editor)->post(route('admin.website.partners.store'), ['name' => 'Bad link', 'website_url' => 'javascript:alert(1)', 'is_published' => 1])
        ->assertSessionHasErrors('website_url');

    $rotary = Partner::query()->where('name', 'Blantyre Rotary Club')->sole();
    actingAs($this->editor)->delete(route('admin.website.partners.destroy', $rotary))->assertSessionHas('success');
    $this->get(route('home'))->assertInertia(fn ($page) => $page->has('partners', 0));
});
