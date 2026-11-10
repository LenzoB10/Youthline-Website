<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Public website content and form submissions (DFD store D8).
 */
return new class extends Migration
{
    public function up(): void
    {
        // site_settings: name, logo, tagline, footer, social links
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('org_name')->nullable();
            $table->string('logo')->nullable();
            $table->string('tagline')->nullable();
            $table->text('footer_text')->nullable();
            $table->json('social_links')->nullable();
            $table->timestamps();
        });

        // pages: editable text for each public page
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            // home, about, programs, impact, get_involved, news, contact
            $table->string('key')->unique();
            $table->string('title')->nullable();
            $table->text('summary')->nullable(); // Home preview
            $table->longText('body')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // core_values: one row per value
        Schema::create('core_values', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        // team_members: leadership / team
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('position')->nullable();
            $table->string('photo')->nullable();
            $table->text('bio')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        // contact_info: contact details (singleton)
        Schema::create('contact_info', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('office_hours')->nullable();
            $table->string('map_location')->nullable();
            $table->timestamps();
        });

        // news_posts: news, activities and success stories
        Schema::create('news_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category')->default('news'); // news, success_story, activity
            $table->longText('body')->nullable();
            $table->string('image')->nullable();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // contact_messages: Contact Us submissions
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('new'); // new, replied
            $table->timestamps();
        });

        // involvement_requests: Get Involved submissions
        Schema::create('involvement_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            // volunteer, mentor, partner, sponsor, donor
            $table->string('type')->default('volunteer');
            $table->text('message')->nullable();
            $table->string('status')->default('new'); // new, contacted, accepted
            $table->timestamps();
        });

        // newsletter_subscribers
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
        Schema::dropIfExists('involvement_requests');
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('news_posts');
        Schema::dropIfExists('contact_info');
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('core_values');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('site_settings');
    }
};
