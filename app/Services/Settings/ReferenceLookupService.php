<?php

namespace App\Services\Settings;

use App\Enums\EducationLevel;
use App\Enums\InstitutionType;
use App\Models\Institution;

/**
 * Turns a typed school name into a shared list entry: an existing one when the name
 * matches (ignoring case and extra spaces), otherwise a new one — so staff can
 * type freely without creating near-duplicates like "zomba secondary " twice.
 */
class ReferenceLookupService
{
    public function institution(?string $name, ?string $level = null): ?Institution
    {
        $name = $this->clean($name);
        if ($name === null) {
            return null;
        }

        return Institution::query()->whereRaw('lower(trim(name)) = ?', [mb_strtolower($name)])->first()
            ?? Institution::create([
                'name' => $name,
                // Year 1–5 means college/university; Form 1–4 means secondary.
                'type' => EducationLevel::tryFrom((string) $level)?->stage() === 'tertiary'
                    ? InstitutionType::University
                    : InstitutionType::Secondary,
            ]);
    }

    protected function clean(?string $value): ?string
    {
        $value = trim(preg_replace('/\s+/', ' ', (string) $value));

        return $value === '' ? null : $value;
    }
}
