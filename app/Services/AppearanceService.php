<?php

namespace App\Services;

use App\Models\AcademySetting;
use Illuminate\Support\Facades\Schema;

class AppearanceService
{
    protected static ?array $cached = null;

    /**
     * Clear memoized cache.
     */
    public static function flush(): void
    {
        static::$cached = null;
        SchoolBrandingService::flush();
    }

    /**
     * Predefined Theme List matching Infix LMS & Edu system
     */
    public static function availableThemes(): array
    {
        return [
            'infix_classic' => [
                'id' => 'infix_classic',
                'name' => 'Infix LMS Classic',
                'title' => 'Infix LMS Purple & Magenta Gradient',
                'description' => 'The iconic Infix LMS signature theme featuring vibrant purple-to-magenta gradients, modern card elevations, and elegant typography.',
                'version' => '2.5.0',
                'author' => 'Infix Design System',
                'image' => '/frontend/infixlmstheme/img/screenshort.jpg',
                'primary_color' => '#7c32ff',
                'secondary_color' => '#202E3B',
                'gradient' => 'linear-gradient(135deg, #7c32ff 0%, #c738d8 100%)',
                'gradient_hover' => 'linear-gradient(90deg, #c738d8 0%, #7c32ff 100%)',
                'accent_color' => '#c738d8',
                'bg_color' => '#f6f7fb',
                'card_bg' => '#ffffff',
                'menu_bg' => '#ffffff',
                'menu_text' => '#202E3B',
                'menu_hover_text' => '#7c32ff',
                'footer_bg' => '#1E2147',
                'footer_headline' => '#ffffff',
                'footer_text' => '#828bb2',
                'footer_text_hover' => '#c738d8',
                'badge' => 'Default',
            ],
            'infix_vibrant' => [
                'id' => 'infix_vibrant',
                'name' => 'Infix LMS Vibrant Pink',
                'title' => 'Infix LMS Passion Pink & Crimson',
                'description' => 'A dynamic, high-energy theme inspired by Infix LMS flagship frontend, with radiant pink highlights and deep contrast.',
                'version' => '2.4.0',
                'author' => 'Infix Design System',
                'image' => '/frontend/infixlmstheme/img/screenshort.jpg',
                'primary_color' => '#FB1159',
                'secondary_color' => '#202E3B',
                'gradient' => 'linear-gradient(135deg, #FB1159 0%, #FF5A36 100%)',
                'gradient_hover' => 'linear-gradient(90deg, #FF5A36 0%, #FB1159 100%)',
                'accent_color' => '#FF5A36',
                'bg_color' => '#f8f9fa',
                'card_bg' => '#ffffff',
                'menu_bg' => '#ffffff',
                'menu_text' => '#202E3B',
                'menu_hover_text' => '#FB1159',
                'footer_bg' => '#1E2147',
                'footer_headline' => '#ffffff',
                'footer_text' => '#828bb2',
                'footer_text_hover' => '#FB1159',
                'badge' => 'Popular',
            ],
            'edume_blue' => [
                'id' => 'edume_blue',
                'name' => 'Edume Modern Ocean',
                'title' => 'Edume Clean Academic Blue',
                'description' => 'The premier Edume LMS theme with clean corporate blues, crisp shadows, and a polished academic feel.',
                'version' => '2.1.0',
                'author' => 'Edume Education',
                'image' => '/frontend/infixlmstheme/img/screenshort.jpg',
                'primary_color' => '#0066FF',
                'secondary_color' => '#1E293B',
                'gradient' => 'linear-gradient(135deg, #0066FF 0%, #00B4D8 100%)',
                'gradient_hover' => 'linear-gradient(90deg, #00B4D8 0%, #0066FF 100%)',
                'accent_color' => '#00B4D8',
                'bg_color' => '#f4f7fe',
                'card_bg' => '#ffffff',
                'menu_bg' => '#ffffff',
                'menu_text' => '#1E293B',
                'menu_hover_text' => '#0066FF',
                'footer_bg' => '#0A1128',
                'footer_headline' => '#ffffff',
                'footer_text' => '#94A3B8',
                'footer_text_hover' => '#38BDF8',
                'badge' => 'Clean',
            ],
            'wetech_emerald' => [
                'id' => 'wetech_emerald',
                'name' => 'WeTech Tech Emerald',
                'title' => 'WeTech Futuristic Green',
                'description' => 'High-tech theme featuring Infix WeTech emerald tones, smooth modern gradients, and optimized dark/light modes.',
                'version' => '2.0.0',
                'author' => 'WeTech Labs',
                'image' => '/frontend/infixlmstheme/img/screenshort.jpg',
                'primary_color' => '#00C895',
                'secondary_color' => '#202E3B',
                'gradient' => 'linear-gradient(135deg, #00C895 0%, #009670 100%)',
                'gradient_hover' => 'linear-gradient(90deg, #009670 0%, #00C895 100%)',
                'accent_color' => '#02B789',
                'bg_color' => '#f0fdf4',
                'card_bg' => '#ffffff',
                'menu_bg' => '#ffffff',
                'menu_text' => '#202E3B',
                'menu_hover_text' => '#00C895',
                'footer_bg' => '#1E2147',
                'footer_headline' => '#ffffff',
                'footer_text' => '#5B5C6E',
                'footer_text_hover' => '#00C895',
                'badge' => 'Modern',
            ],
            'royal_indigo' => [
                'id' => 'royal_indigo',
                'name' => 'Royal Indigo & Violet',
                'title' => 'Royal Academic Executive',
                'description' => 'Executive design with deep indigo and royal violet accents, suited for universities and elite academies.',
                'version' => '1.9.0',
                'author' => 'Infix Design System',
                'image' => '/frontend/infixlmstheme/img/screenshort.jpg',
                'primary_color' => '#6366F1',
                'secondary_color' => '#0F172A',
                'gradient' => 'linear-gradient(135deg, #6366F1 0%, #8B5CF6 100%)',
                'gradient_hover' => 'linear-gradient(90deg, #8B5CF6 0%, #6366F1 100%)',
                'accent_color' => '#8B5CF6',
                'bg_color' => '#F5F3FF',
                'card_bg' => '#ffffff',
                'menu_bg' => '#ffffff',
                'menu_text' => '#0F172A',
                'menu_hover_text' => '#6366F1',
                'footer_bg' => '#0F172A',
                'footer_headline' => '#ffffff',
                'footer_text' => '#94A3B8',
                'footer_text_hover' => '#A78BFA',
                'badge' => 'Executive',
            ],
            'dark_sapphire' => [
                'id' => 'dark_sapphire',
                'name' => 'Dark Sapphire Elegance',
                'title' => 'Deep Dark Mode Infix',
                'description' => 'A dark theme crafted for nighttime productivity, OLED contrast, and neon purple accents.',
                'version' => '2.2.0',
                'author' => 'Infix Studio',
                'image' => '/frontend/infixlmstheme/img/screenshort.jpg',
                'primary_color' => '#818CF8',
                'secondary_color' => '#1E293B',
                'gradient' => 'linear-gradient(135deg, #6366F1 0%, #A855F7 100%)',
                'gradient_hover' => 'linear-gradient(90deg, #A855F7 0%, #6366F1 100%)',
                'accent_color' => '#A855F7',
                'bg_color' => '#0B0F19',
                'card_bg' => '#13192B',
                'menu_bg' => '#0B0F19',
                'menu_text' => '#F8FAFC',
                'menu_hover_text' => '#818CF8',
                'footer_bg' => '#060911',
                'footer_headline' => '#ffffff',
                'footer_text' => '#64748B',
                'footer_text_hover' => '#818CF8',
                'badge' => 'OLED Dark',
            ],
        ];
    }

    /**
     * Get active appearance configuration
     */
    public static function get(): array
    {
        if (static::$cached !== null) {
            return static::$cached;
        }

        $settings = [];
        try {
            if (Schema::hasTable('academy_settings')) {
                $settings = AcademySetting::pluck('value', 'key')->toArray();
            }
        } catch (\Throwable $e) {
            $settings = [];
        }

        $available = static::availableThemes();
        $activeKey = $settings['active_theme'] ?? 'infix_classic';
        if (! isset($available[$activeKey])) {
            $activeKey = 'infix_classic';
        }

        $base = $available[$activeKey];

        // Allow custom color overrides from settings if customized
        $primaryColor = $settings['theme_primary_color'] ?? $base['primary_color'];
        $secondaryColor = $settings['theme_secondary_color'] ?? $base['secondary_color'];
        $gradient = $settings['theme_primary_gradient'] ?? $base['gradient'];
        $accentColor = $settings['theme_accent_color'] ?? $base['accent_color'];
        $bgColor = $settings['theme_bg_color'] ?? $base['bg_color'];
        $footerBg = $settings['theme_footer_bg'] ?? $base['footer_bg'];
        $footerText = $settings['theme_footer_text'] ?? $base['footer_text'];

        $fontFamily1 = $settings['theme_font_family1'] ?? 'Poppins';
        $fontFamily2 = $settings['theme_font_family2'] ?? 'Montserrat';
        $preloaderStatus = ($settings['preloader_status'] ?? '1') !== '0';
        $preloaderStyle = $settings['preloader_style'] ?? 'rocket'; // 'rocket', 'circle', 'pulse'

        static::$cached = [
            'active_key' => $activeKey,
            'theme' => array_merge($base, [
                'primary_color' => $primaryColor,
                'secondary_color' => $secondaryColor,
                'gradient' => $gradient,
                'accent_color' => $accentColor,
                'bg_color' => $bgColor,
                'footer_bg' => $footerBg,
                'footer_text' => $footerText,
            ]),
            'fonts' => [
                'family1' => $fontFamily1,
                'family2' => $fontFamily2,
                'google_fonts_url' => 'https://fonts.googleapis.com/css2?family='.urlencode($fontFamily1).':wght@300;400;500;600;700;800;900&family='.urlencode($fontFamily2).':wght@400;500;600;700;800&display=swap',
            ],
            'preloader' => [
                'status' => $preloaderStatus,
                'style' => $preloaderStyle,
            ],
            'available_themes' => $available,
        ];

        return static::$cached;
    }

    /**
     * Activate a theme by key
     */
    public static function setActiveTheme(string $themeKey): bool
    {
        $available = static::availableThemes();
        if (! isset($available[$themeKey])) {
            return false;
        }

        $theme = $available[$themeKey];
        AcademySetting::set('active_theme', $themeKey);
        AcademySetting::set('theme_primary_color', $theme['primary_color']);
        AcademySetting::set('theme_secondary_color', $theme['secondary_color']);
        AcademySetting::set('theme_primary_gradient', $theme['gradient']);
        AcademySetting::set('theme_accent_color', $theme['accent_color']);
        AcademySetting::set('theme_bg_color', $theme['bg_color']);
        AcademySetting::set('theme_footer_bg', $theme['footer_bg']);
        AcademySetting::set('theme_footer_text', $theme['footer_text']);
        AcademySetting::set('brand_primary_color', $theme['primary_color']);

        static::flush();

        return true;
    }

    /**
     * Save custom color palette
     */
    public static function saveCustomColors(array $colors): void
    {
        if (! empty($colors['primary_color'])) {
            AcademySetting::set('theme_primary_color', $colors['primary_color']);
            AcademySetting::set('brand_primary_color', $colors['primary_color']);
        }
        if (! empty($colors['secondary_color'])) {
            AcademySetting::set('theme_secondary_color', $colors['secondary_color']);
        }
        if (! empty($colors['gradient'])) {
            AcademySetting::set('theme_primary_gradient', $colors['gradient']);
        }
        if (! empty($colors['accent_color'])) {
            AcademySetting::set('theme_accent_color', $colors['accent_color']);
        }
        if (! empty($colors['bg_color'])) {
            AcademySetting::set('theme_bg_color', $colors['bg_color']);
        }
        if (! empty($colors['footer_bg'])) {
            AcademySetting::set('theme_footer_bg', $colors['footer_bg']);
        }
        if (! empty($colors['footer_text'])) {
            AcademySetting::set('theme_footer_text', $colors['footer_text']);
        }

        static::flush();
    }

    /**
     * Save font configuration
     */
    public static function saveFonts(string $family1, string $family2): void
    {
        AcademySetting::set('theme_font_family1', $family1);
        AcademySetting::set('theme_font_family2', $family2);
        static::flush();
    }

    /**
     * Save preloader settings
     */
    public static function savePreloader(bool $status, string $style): void
    {
        AcademySetting::set('preloader_status', $status ? '1' : '0');
        AcademySetting::set('preloader_style', $style);
        static::flush();
    }
}
