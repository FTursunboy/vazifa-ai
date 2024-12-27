<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendMessageToInactiveUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sendNotificationToInactiveUsers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $telegramService = new TelegramService();
        $now = Carbon::now();

        if (!$now->isBetween($now->copy()->setTime(9, 0), $now->copy()->setTime(18, 0))) {
            $this->info('Не рабочее время. Уведомления отправляться не будут.');
            return;
        }

        $inactiveUsers = User::where('last_seen', '<', $now->subHours(18))
            ->whereNull('is_authed')
            ->get();

        foreach ($inactiveUsers as $user) {
            $telegramService->sendMessage(
                $user->telegram_id,
                "Здравствуйте! Можем ли мы вам чем-то помочь?"
            );

            $this->info("Уведомление отправлено пользователю с Telegram ID: {$user->telegram_id}");
        }
    }
}
