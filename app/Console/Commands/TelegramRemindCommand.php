<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramRemindCommand extends Command
{
    protected $signature = 'telegram:remind {title : Reminder Title} {details? : Detailed reminder notes} {--url= : Optional action URL}';

    protected $description = 'Dispatch an immediate Telegram reminder to the configured bot and chat ID';

    public function handle(): int
    {
        $title = $this->argument('title');
        $details = $this->argument('details') ?: 'Automated PrimeOS reminder.';
        $url = $this->option('url');

        $this->info('Dispatching Telegram reminder to default chat...');

        $sent = TelegramService::sendReminder($title, $details, $url);

        if ($sent) {
            $this->info('✅ Telegram reminder successfully delivered!');

            return Command::SUCCESS;
        }

        $this->error('❌ Failed to deliver Telegram reminder. Check logs for details.');

        return Command::FAILURE;
    }
}
