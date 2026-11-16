<?php

namespace App\Services\Students;

use App\Enums\DocumentType;
use App\Models\Consent;
use App\Models\Document;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Student documents (birth certificates, consent forms…) are private: they live on
 * the non-public "local" disk and are only served through an authorised route.
 */
class StudentDocumentService
{
    protected const DISK = 'local';

    public function store(Student $student, UploadedFile $file, DocumentType $type, User $by): Document
    {
        $path = Storage::disk(self::DISK)->putFile("documents/{$student->id}", $file);

        return Document::create([
            'student_id' => $student->id,
            'type' => $type,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $by->getKey(),
            'uploaded_at' => now(),
        ]);
    }

    public function exists(Document $document): bool
    {
        return filled($document->file_path) && Storage::disk(self::DISK)->exists($document->file_path);
    }

    /**
     * Inline = open in the browser (PDFs and photos only — uploads are limited to those).
     */
    public function download(Document $document, bool $inline = false): StreamedResponse
    {
        $name = $document->original_name ?: basename((string) $document->file_path);
        $headers = ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'];

        return Storage::disk(self::DISK)->response(
            $document->file_path,
            $name,
            $headers,
            $inline ? 'inline' : 'attachment',
        );
    }

    public function delete(Document $document): void
    {
        DB::transaction(function () use ($document) {
            if (filled($document->file_path)) {
                Storage::disk(self::DISK)->delete($document->file_path);
            }

            $document->delete();
        });
    }

    /**
     * Record the guardian's consent. Allowing a photo or story requires the signed form.
     *
     * @param  array{photo_allowed: bool, story_allowed: bool, signed_on: ?string, document_id: ?int}  $data
     */
    public function recordConsent(Student $student, array $data): Consent
    {
        return Consent::query()->updateOrCreate(['student_id' => $student->id], $data);
    }
}
