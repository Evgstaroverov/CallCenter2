<?php

namespace App\Console\Commands;

use App\Services\TelegramPollingService;
use Illuminate\Console\Command;

class TelegramPolling extends Command
{
    protected $signature = 'telegram:poll';
    protected $description = 'Start Telegram Long Polling';

    public function handle()
    {
        $service = new TelegramPollingService();
        $offset = 0;

        $this->info('Starting Telegram Long Polling...');

        while (true) {
            $updates = $service->getUpdates($offset);

            foreach ($updates as $update) {
                $offset = $update['update_id'] + 1;
                $msg = $service->saveMessage($update);

                if ($msg) {
                    $this->info("New message from {$msg->first_name}: {$msg->text}");
                }
            }

            // Пауза между запросами
            sleep(1);
        }
    }
}