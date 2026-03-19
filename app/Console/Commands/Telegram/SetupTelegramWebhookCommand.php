<?php

namespace App\Console\Commands\Telegram;

use Illuminate\Console\Command;
use Telegram\Bot\Laravel\Facades\Telegram;
use Exception;

class SetupTelegramWebhookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sage:telegram-setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically registers the Telegram webhook URL in Telegram API';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $token = config('telegram.bots.' . config('telegram.default') . '.token');
        $appUrl = config('app.url');

        if (empty($token) || $token === 'YOUR-BOT-TOKEN') {
            $this->error('TELEGRAM_BOT_TOKEN is not set in .env');
            return Command::FAILURE;
        }

        if (empty($appUrl)) {
            $this->error('APP_URL is not set in .env');
            return Command::FAILURE;
        }

        $webhookUrl = trim($appUrl, '/') . '/api/v1/telegram/webhook';

        $this->info("Setting up webhook for URL: {$webhookUrl}");

        try {
            $response = Telegram::setWebhook(['url' => $webhookUrl]);

            if ($response) {
                $this->info('Success: Telegram webhook has been set!');
                $this->info("URL: {$webhookUrl}");
                return Command::SUCCESS;
            }

            $this->error('Failed to set Telegram webhook.');
            return Command::FAILURE;
        } catch (Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
