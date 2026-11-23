<?php

namespace Tests\Feature;

use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_telegram_service_dispatches_message(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 12345]], 200),
        ]);

        $sent = TelegramService::sendMessage('<b>Hello Telegram Test</b>');
        $this->assertTrue($sent);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.telegram.org') &&
                   $request['chat_id'] === '8130847012' &&
                   str_contains($request['text'], 'Hello Telegram Test');
        });
    }

    public function test_telegram_reminder_and_artisan_command(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 12346]], 200),
        ]);

        $this->artisan('telegram:remind', [
            'title' => 'Daily Standup',
            'details' => 'Review curriculum modules',
        ])->assertExitCode(0);

        Http::assertSent(function ($request) {
            return str_contains($request['text'], 'Daily Standup') &&
                   str_contains($request['text'], 'Review curriculum modules');
        });
    }
}
