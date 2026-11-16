<?php

use App\Models\Consent;
use App\Models\Document;
use App\Models\NewsPost;
use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

require_once __DIR__.'/helpers.php';

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
    ['admin' => $this->admin, 'manager' => $this->manager, 'outsider' => $this->outsider] = studentActors();
    $this->student = makeLearner();
});

function uploadDocument(Student $student, string $type = 'birth_cert', ?UploadedFile $file = null)
{
    return test()->post(route('students.documents.store', $student), [
        'type' => $type,
        'file' => $file ?? UploadedFile::fake()->create('birth certificate.pdf', 200, 'application/pdf'),
    ]);
}

// --- Uploads -----------------------------------------------------------------

it('stores uploads privately, never on the public disk', function () {
    actingAs($this->manager);
    uploadDocument($this->student)->assertSessionHas('success');

    $document = Document::query()->sole();
    expect($document->original_name)->toBe('birth certificate.pdf')
        ->and($document->type->value)->toBe('birth_cert')
        ->and($document->uploaded_by)->toBe($this->manager->id)
        ->and($document->file_path)->toStartWith("documents/{$this->student->id}/");

    Storage::disk('local')->assertExists($document->file_path);
    expect(Storage::disk('public')->allFiles())->toBe([]);
});

it('only accepts PDFs and photos up to 10 MB', function () {
    actingAs($this->manager);

    uploadDocument($this->student, file: UploadedFile::fake()->create('evil.svg', 5, 'image/svg+xml'))->assertSessionHasErrors('file');
    uploadDocument($this->student, file: UploadedFile::fake()->create('page.html', 5, 'text/html'))->assertSessionHasErrors('file');
    uploadDocument($this->student, file: UploadedFile::fake()->create('huge.pdf', 10241, 'application/pdf'))->assertSessionHasErrors('file');
    uploadDocument($this->student, 'passport')->assertSessionHasErrors('type');

    expect(Document::query()->count())->toBe(0);
});

// --- Viewing -----------------------------------------------------------------

it('serves documents only to permitted staff, and only through their own student', function () {
    actingAs($this->manager);
    uploadDocument($this->student);
    $document = Document::query()->sole();
    $other = makeLearner(attributes: ['first_name' => 'Other']);

    actingAs($this->manager)->get(route('students.documents.show', [$this->student, $document]))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    actingAs($this->manager)->get(route('students.documents.show', [$this->student, $document, 'download' => 1]))
        ->assertDownload('birth certificate.pdf');

    actingAs($this->outsider)->get(route('students.documents.show', [$this->student, $document]))->assertForbidden();
    actingAs($this->manager)->get(route('students.documents.show', [$other, $document]))->assertNotFound();
});

it('returns not found when a document\'s file is missing', function () {
    $document = Document::create(['student_id' => $this->student->id, 'type' => 'other', 'file_path' => 'documents/missing.pdf']);

    actingAs($this->manager)->get(route('students.documents.show', [$this->student, $document]))->assertNotFound();
});

// --- Deleting ----------------------------------------------------------------

it('lets only the Admin delete documents, removing the file too', function () {
    actingAs($this->manager);
    uploadDocument($this->student);
    $document = Document::query()->sole();

    actingAs($this->manager)->delete(route('students.documents.destroy', [$this->student, $document]))->assertForbidden();

    actingAs($this->admin)->delete(route('students.documents.destroy', [$this->student, $document]))->assertSessionHas('success');

    Storage::disk('local')->assertMissing($document->file_path);
    expect(Document::query()->count())->toBe(0);
});

it('removes stored files when the Admin deletes a student', function () {
    actingAs($this->manager);
    uploadDocument($this->student);
    $path = Document::query()->value('file_path');

    actingAs($this->admin)->delete(route('students.destroy', $this->student), ['confirm_name' => 'Thandiwe Banda']);

    Storage::disk('local')->assertMissing($path);
});

// --- Consent -----------------------------------------------------------------

it('requires this student\'s signed consent form before allowing a photo or story', function () {
    actingAs($this->manager);
    uploadDocument($this->student, 'consent');
    uploadDocument($this->student, 'birth_cert');
    $consentForm = Document::query()->where('type', 'consent')->sole();
    $birthCert = Document::query()->where('type', 'birth_cert')->sole();

    $other = makeLearner(attributes: ['first_name' => 'Other']);
    uploadDocument($other, 'consent');
    $othersForm = Document::query()->where('student_id', $other->id)->sole();

    $consent = fn (array $data) => actingAs($this->manager)->put(route('students.consent.update', $this->student), [
        'photo_allowed' => false, 'story_allowed' => true, 'signed_on' => '2026-02-01', ...$data,
    ]);

    $consent(['document_id' => null])->assertSessionHasErrors('document_id');
    $consent(['document_id' => $birthCert->id])->assertSessionHasErrors('document_id');
    $consent(['document_id' => $othersForm->id])->assertSessionHasErrors('document_id');
    $consent(['document_id' => $consentForm->id, 'signed_on' => null])->assertSessionHasErrors('signed_on');
    $consent(['document_id' => $consentForm->id])->assertSessionHasNoErrors();

    expect(Consent::query()->sole()->story_allowed)->toBeTrue();

    // Saying "no" to both needs no form.
    actingAs($this->manager)->put(route('students.consent.update', $other), [
        'photo_allowed' => false, 'story_allowed' => false,
    ])->assertSessionHasNoErrors();
});

it('protects the consent form that backs active consent, and withdrawing consent hides published stories', function () {
    actingAs($this->manager);
    uploadDocument($this->student, 'consent');
    $form = Document::query()->sole();
    actingAs($this->manager)->put(route('students.consent.update', $this->student), [
        'photo_allowed' => true, 'story_allowed' => true, 'signed_on' => '2026-02-01', 'document_id' => $form->id,
    ]);
    NewsPost::create(['title' => 'Thandiwe\'s story', 'student_id' => $this->student->id, 'is_published' => true, 'published_at' => now()]);

    expect(NewsPost::query()->publiclyVisible()->count())->toBe(1);

    actingAs($this->admin)->delete(route('students.documents.destroy', [$this->student, $form]))->assertSessionHas('error');
    expect(Document::query()->count())->toBe(1);

    actingAs($this->manager)->put(route('students.consent.update', $this->student), [
        'photo_allowed' => false, 'story_allowed' => false, 'signed_on' => '2026-02-01', 'document_id' => $form->id,
    ])->assertSessionHasNoErrors();

    expect(NewsPost::query()->publiclyVisible()->count())->toBe(0);
});
