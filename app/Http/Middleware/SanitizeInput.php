<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    /**
     * Fields exempt from HTML tag stripping (e.g. passwords).
     */
    protected array $except = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
    ];

    /**
     * Handle an incoming request and sanitize inputs.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();

        if (!empty($input)) {
            $sanitized = $this->sanitizeArray($input);
            $request->merge($sanitized);
        }

        return $next($request);
    }

    /**
     * Recursively clean strings against XSS, null bytes, and malicious tags.
     */
    protected function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $this->except, true)) {
                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $data[$key] = $this->sanitizeString($value);
            }
        }

        return $data;
    }

    /**
     * Sanitize a single string value.
     */
    protected function sanitizeString(string $value): string
    {
        // 1. Remove null bytes
        $clean = str_replace(chr(0), '', $value);

        // 2. Strip embedded script, style, and iframe tags along with their entire contents
        $clean = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $clean);
        $clean = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $clean);
        $clean = preg_replace('/<iframe\b[^>]*>(.*?)<\/iframe>/is', '', $clean);

        // 3. Strip all remaining HTML and PHP tags to eliminate XSS vectors
        $clean = strip_tags($clean);

        // 4. Remove dangerous JS URI protocols and inline event handlers
        $clean = preg_replace('/javascript\s*:/i', '', $clean);
        $clean = preg_replace('/vbscript\s*:/i', '', $clean);
        $clean = preg_replace('/onload\s*=/i', '', $clean);
        $clean = preg_replace('/onerror\s*=/i', '', $clean);

        // 5. Remove zero-width and invisible control characters
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $clean);

        // 6. Trim whitespace
        return trim($clean);
    }
}
