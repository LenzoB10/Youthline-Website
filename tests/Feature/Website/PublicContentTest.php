<?php

use App\Models\CoreValue;
use App\Models\Page;
use App\Models\Program;
use App\Models\TeamMember;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;

it('passes programs, values and the about summary to the home page', function () {
    Program::create(['name' => 'Education', 'slug' => 'education', 'summary' => 'Scholarships', 'display_order' => 1]);
    CoreValue::create(['name' => 'Compassion', 'description' => 'Kindness', 'display_order' => 1]);
    Page::create(['key' => 'about', 'title' => 'About', 'summary' => 'About summary', 'body' => 'body']);

    get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Public/Home')
            ->has('programs', 1)
            ->has('values', 1)
            ->where('aboutSummary', 'About summary')
        );
});

it('passes vision, mission, values and team to the about page', function () {
    Page::create(['key' => 'about', 'title' => 'About', 'summary' => 's', 'body' => 'Intro paragraph.']);
    Page::create(['key' => 'vision', 'title' => 'Our Vision', 'body' => 'Vision text']);
    Page::create(['key' => 'mission', 'title' => 'Our Mission', 'body' => 'Mission text']);
    CoreValue::create(['name' => 'Integrity', 'description' => 'Accountable', 'display_order' => 1]);
    TeamMember::create(['name' => 'Jane Doe', 'position' => 'Director', 'display_order' => 1]);

    get(route('about'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Public/About')
            ->where('vision.title', 'Our Vision')
            ->where('mission.body', 'Mission text')
            ->has('values', 1)
            ->has('team', 1)
        );
});

it('lists all programs on the programs page in display order', function () {
    Program::create(['name' => 'Careers', 'slug' => 'careers', 'summary' => 's', 'display_order' => 2]);
    Program::create(['name' => 'Education', 'slug' => 'education', 'summary' => 's', 'display_order' => 1]);

    get(route('programs'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Public/Programs')
            ->has('programs', 2)
            ->where('programs.0.name', 'Education')
        );
});
