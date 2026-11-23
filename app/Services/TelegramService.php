<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    public static function getBotToken(): string
    {
        return (string) (config('services.telegram.bot_token') ?? env('TELEGRAM_BOT_TOKEN', ''));
    }

    public static function getDefaultChatId(): string
    {
        return (string) (config('services.telegram.chat_id') ?? env('TELEGRAM_CHAT_ID', ''));
    }

    /**
     * Send raw text/HTML message to Telegram.
     */
    public static function sendMessage(string $text, ?string $chatId = null, string $parseMode = 'HTML'): bool
    {
        $token = self::getBotToken();
        $targetChatId = $chatId ?: self::getDefaultChatId();

        if (empty($token) || empty($targetChatId)) {
            Log::warning('Telegram message skipped: missing token or chatId.');

            return false;
        }

        try {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $targetChatId,
                'text' => $text,
                'parse_mode' => $parseMode,
                'disable_web_page_preview' => true,
            ]);

            if (! $response->successful()) {
                Log::error('Telegram API error response: '.$response->body());

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send Telegram message: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Send formatted reminder card.
     */
    public static function sendReminder(string $title, string $details, ?string $actionUrl = null): bool
    {
        $timeStr = now()->format('d M Y, h:i A');
        $school = SchoolBrandingService::get();
        $schoolName = $school['name'] ?? 'PrimeOS School';
        $msg = "🔔 <b>[{$schoolName} Reminder]</b>\n\n";
        $msg .= "📌 <b>{$title}</b>\n";
        $msg .= "📝 {$details}\n";
        $msg .= "⏱️ <i>{$timeStr}</i>\n";

        if ($actionUrl) {
            $msg .= "\n🔗 <a href=\"{$actionUrl}\">Open in LMS</a>";
        }

        return self::sendMessage($msg);
    }

    /**
     * Send formatted status/alert update.
     */
    public static function sendAlert(string $title, string $message, string $level = 'info'): bool
    {
        $icon = match ($level) {
            'success' => '✅',
            'warning' => '⚠️',
            'danger', 'error' => '🚨',
            default => 'ℹ️',
        };

        $msg = "{$icon} <b>{$title}</b>\n\n";
        $msg .= "{$message}\n";
        $msg .= "\n📅 ".now()->format('Y-m-d H:i:s');

        return self::sendMessage($msg);
    }
}
