<?php

namespace App\Enums;

enum DocumentType: string
{
    case BirthCertificate = 'birth_cert';
    case Admission = 'admission';
    case ReportCard = 'report_card';
    case Receipt = 'receipt';
    case Consent = 'consent';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::BirthCertificate => 'Birth certificate',
            self::Admission => 'Admission letter',
            self::ReportCard => 'Report card',
            self::Receipt => 'Receipt',
            self::Consent => 'Consent form',
            self::Other => 'Other',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $type) => ['value' => $type->value, 'label' => $type->label()], self::cases());
    }
}
