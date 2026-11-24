<?php

namespace App\Services;

use App\Models\SchoolSetting;
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
        return static::get()['name'] ?? config('app.name', 'School ERP');
    }

    /**
     * Instance accessor for branding profile.
     */
    public function getBranding(): array
    {
        return static::get();
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
        try {
            if (Schema::hasTable('academy_settings')) {
                $settings = SchoolSetting::pluck('value', 'key')->toArray();
            }
        } catch (\Throwable $e) {
            $settings = [];
        }

        $defaultName = config('school.institution_name') ?: config('app.name', 'School ERP');
        $rawName = ! empty($settings['institute_name']) ? trim($settings['institute_name']) : $defaultName;

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

        $shortName = ! empty($settings['school_short_name']) ? trim($settings['school_short_name']) : ($autoShort ?: 'ERP');
        $currentYear = (int) date('Y');
        $defaultSession = $currentYear . '–' . ($currentYear + 1);

        static::$cached = [
            'name' => $rawName,
            'short_name' => $shortName,
            'campus' => $settings['campus_name'] ?? 'Main Campus',
            'motto' => $settings['motto'] ?? 'Excellence • Knowledge • Integrity',
            'logo_url' => $settings['school_logo_url'] ?? null,
            'favicon_url' => $settings['school_favicon_url'] ?? null,
            'affiliation' => $settings['affiliation_number'] ?? '',
            'board_name' => $settings['board_name'] ?? 'General Board',
            'academic_session' => $settings['academic_session'] ?? $defaultSession,
            'principal' => $settings['principal_name'] ?? 'Principal Office',
            'phone' => $settings['institution_phone'] ?? '',
            'email' => $settings['institution_email'] ?? config('mail.from.address', 'admin@school.local'),
            'address' => $settings['institution_address'] ?? '',
            'upi_vpa' => $settings['upi_vpa'] ?? null,
            'currency_symbol' => $settings['currency_symbol'] ?? config('school.currency_symbol', '$'),
            'primary_color' => $settings['brand_primary_color'] ?? ($settings['theme_primary_color'] ?? '#7c32ff'),
            'system_name' => $settings['system_name'] ?? config('app.name', 'School ERP'),
            'brand_mark' => $settings['brand_mark'] ?? ('Powered by ' . ($settings['system_name'] ?? config('app.name', 'School ERP'))),
            'company_name' => $settings['company_name'] ?? $rawName,
        ];

        return static::$cached;
    }
}
