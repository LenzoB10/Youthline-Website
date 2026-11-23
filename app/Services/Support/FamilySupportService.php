<?php

namespace App\Services\Support;

use App\Models\FamilySupport;
use App\Models\Household;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Records the support Youthline has given a family.
 */
class FamilySupportService
{
    public function __construct(protected PhotoService $photos) {}

    /**
     * @param  array{given_on: string, type: string, description: string, amount?: ?float, note?: ?string}  $data
     * @param  array<int, UploadedFile>  $photos
     */
    public function record(Household $household, array $data, array $photos, User $by): FamilySupport
    {
        if ($photos && ! $household->photoConsentGiven()) {
            throw ValidationException::withMessages([
                'photos' => 'This family has not given photo consent, so photos can\'t be added. Record the consent first.',
            ]);
        }

        return DB::transaction(function () use ($household, $data, $photos, $by) {
            $support = $household->familySupports()->create([
                'given_on' => $data['given_on'],
                'type' => $data['type'],
                'description' => $data['description'],
                'amount' => $data['amount'] ?? null,
                'note' => $data['note'] ?? null,
                'created_by' => $by->id,
            ]);
            $this->photos->storeMany($support, $photos, 'support', $by);

            return $support;
        });
    }

    public function delete(FamilySupport $support): void
    {
        DB::transaction(function () use ($support) {
            $this->photos->deleteAllFor($support);
            $support->delete();
        });
    }
}
