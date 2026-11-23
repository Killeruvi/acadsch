<?php

namespace App\Services;

use App\Models\AcademySetting;
use Illuminate\Support\Facades\Schema;

class SchoolBrandingService
{
    protected static ?array $cached = null;

    /**
     * Clear memoized cache (e.g. after updating settings).
     */
    public static function flush(): void
    {
        static::$cached = null;
    }

    /**
     * Get institutional school name.
     */
    public static function getInstituteName(): string
    {
        return static::get()['name'] ?? 'School';
    }

    /**
     * Get institutional branding profile.
     */
    public static function get(): array
    {
        if (static::$cached !== null) {
            return static::$cached;
        }

        $settings = [];
        if (Schema::hasTable('academy_settings')) {
            $settings = AcademySetting::pluck('value', 'key')->toArray();
        }

        $rawName = $settings['institute_name'] ?? 'PrimeOS Academy';

        // Generate a concise 2-4 letter short code / initials if not explicitly set
        $words = preg_split('/\s+/', trim($rawName));
        $autoShort = '';
        foreach ($words as $w) {
            if (! empty($w) && strlen($autoShort) < 3) {
                $autoShort .= strtoupper($w[0]);
            }
        }
        if (strlen($autoShort) < 2) {
            $autoShort = strtoupper(substr($rawName, 0, 3));
        }

        $shortName = ! empty($settings['school_short_name']) ? trim($settings['school_short_name']) : $autoShort;

        static::$cached = [
            'name' => $rawName,
            'short_name' => $shortName,
            'campus' => $settings['campus_name'] ?? 'Not configured',
            'motto' => $settings['motto'] ?? 'Aim, Ascend, Achieve • Where Passion Meets Precision',
            'logo_url' => $settings['school_logo_url'] ?? null,
            'favicon_url' => $settings['school_favicon_url'] ?? null,
            'affiliation' => $settings['affiliation_number'] ?? 'Not configured',
            'board_name' => $settings['board_name'] ?? 'CBSE',
            'academic_session' => $settings['academic_session'] ?? 'Not configured',
            'principal' => $settings['principal_name'] ?? 'Not configured',
            'phone' => $settings['institution_phone'] ?? null,
            'email' => $settings['institution_email'] ?? null,
            'address' => $settings['institution_address'] ?? null,
            'upi_vpa' => $settings['upi_vpa'] ?? null,
            'currency_symbol' => $settings['currency_symbol'] ?? '₹',
            'primary_color' => $settings['brand_primary_color'] ?? ($settings['theme_primary_color'] ?? '#7c32ff'),
            'system_name' => 'PrimeOS',
            'brand_mark' => 'Powered by PrimeOS',
            'company_name' => 'PrimeOS',
        ];

        return static::$cached;
    }
}
