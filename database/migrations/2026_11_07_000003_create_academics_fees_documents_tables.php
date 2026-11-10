<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documents & consent (D7) and academics & fees (D3, D4).
 * Documents are created before results/payments because they reference them.
 */
return new class extends Migration
{
    public function up(): void
    {
        // documents: uploaded files on a student profile
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            // birth_cert, admission, report_card, receipt, consent, other
            $table->string('type')->default('other');
            $table->string('file_path')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();
        });

        // consents: what each guardian has agreed to
        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->boolean('photo_allowed')->default(false);
            $table->boolean('story_allowed')->default(false);
            $table->date('signed_on')->nullable();
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        // academic_results: term, MSCE and university results
        Schema::create('academic_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('term_id')->nullable()->constrained()->nullOnDelete();
            $table->string('exam_type')->default('term'); // term, msce, university
            $table->decimal('average', 6, 2)->nullable();
            $table->string('position')->nullable();
            $table->foreignId('report_card_document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // subject_grades: grade per subject for a result
        Schema::create('subject_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_id')->constrained('academic_results')->cascadeOnDelete();
            $table->string('subject');
            $table->string('grade')->nullable();
            $table->timestamps();
        });

        // fees: what is owed each term and Youthline's share
        Schema::create('fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('institution_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount_owed', 12, 2)->default(0);
            $table->decimal('youthline_share', 12, 2)->default(0);
            $table->timestamps();
        });

        // payments: every payment Youthline makes, with receipt and approval
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('paid_on')->nullable();
            $table->string('method')->nullable();
            $table->string('reference')->nullable();
            $table->foreignId('receipt_document_id')->nullable()->constrained('documents')->nullOnDelete();
            // pending_approval, paid, error
            $table->string('status')->default('paid');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('fees');
        Schema::dropIfExists('subject_grades');
        Schema::dropIfExists('academic_results');
        Schema::dropIfExists('consents');
        Schema::dropIfExists('documents');
    }
};
