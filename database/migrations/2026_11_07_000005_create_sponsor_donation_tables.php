<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sponsors & donations (DFD store D6). Donations store Stripe references only,
 * never card details.
 */
return new class extends Migration
{
    public function up(): void
    {
        // sponsors: people or organisations
        Schema::create('sponsors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('person'); // person, organisation
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('stripe_customer_id')->nullable();
            $table->timestamps();
        });

        // sponsorships: which students a sponsor supports
        Schema::create('sponsorships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sponsor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('amount_per_year', 12, 2)->nullable();
            $table->timestamps();
        });

        // donations: every donation made through Stripe
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sponsor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('donor_name')->nullable();
            $table->string('donor_email')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->decimal('amount', 12, 2)->default(0);
            $table->char('currency', 3)->default('USD');
            $table->string('type')->default('one_time'); // one_time, monthly
            $table->string('cause')->default('general'); // general, program, sponsor_student
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            // pending, paid, failed, refunded, cancelled
            $table->string('status')->default('pending');
            $table->string('stripe_session_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_subscription_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
        Schema::dropIfExists('sponsorships');
        Schema::dropIfExists('sponsors');
    }
};
