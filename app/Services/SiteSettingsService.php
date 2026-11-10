<?php

namespace App\Services;

use App\Models\ContactInfo;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

class SiteSettingsService
{
    protected const CACHE_KEY = 'public.site_data';

    protected const DEFAULT_TAGLINE = 'Empowering Young People. Transforming Lives. Building Futures.';

    /**
     * Branding + contact details shared across the public website.
     * Cached so it costs nothing on normal requests.
     *
     * @return array{org_name: string, tagline: string, logo: ?string, footer_text: ?string, social_links: array<string, string>, contact: array{phone: ?string, email: ?string, address: ?string, office_hours: ?string}}
     */
    public function data(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addHour(), function (): array {
            $settings = SiteSetting::query()->first();
            $contact = ContactInfo::query()->first();

            return [
                'org_name' => $settings->org_name ?? config('app.name', 'Youthline'),
                'tagline' => $settings->tagline ?? self::DEFAULT_TAGLINE,
                'logo' => $settings->logo ?? null,
                'footer_text' => $settings->footer_text ?? null,
                'social_links' => $settings->social_links ?? [],
                'contact' => [
                    'phone' => $contact->phone ?? null,
                    'email' => $contact->email ?? null,
                    'address' => $contact->address ?? null,
                    'office_hours' => $contact->office_hours ?? null,
                ],
            ];
        });
    }

    /**
     * Clear the cached site data (call after the Admin edits settings).
     */
    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
