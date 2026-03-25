<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\GuildIntegration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Telegram\Bot\Laravel\Facades\Telegram;
use App\Http\Resources\Api\Discord\EventFullResource;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateMessengerEventMessage implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $eventId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $eventId)
    {
        $this->eventId = $eventId;
    }

    /**
     * The unique ID of the job.
     */
    public function uniqueId(): string
    {
        return (string) $this->eventId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $event = Event::with(['guild', 'squads.participants.user.profile', 'participants.user.profile'])->find($this->eventId);
        if (!$event || $event->status === 'draft') {
            return;
        }

        $platforms = $event->notification_settings['platforms'] ?? ['discord', 'telegram'];

        // 1. Обновление в Telegram
        if (in_array('telegram', $platforms)) {
            $this->updateTelegram($event);
        }

        // 2. Обновление в Discord (отправка в botJs через HTTP)
        if (in_array('discord', $platforms)) {
            $this->updateDiscord($event);
        }
    }

    protected function updateTelegram(Event $event)
    {
        $integration = $event->guild->integrations()
            ->where('provider', 'telegram')
            ->first();

        if (!$integration || !$integration->platform_id) {
            return;
        }

        $text = $this->formatTelegramMessage($event);
        $botName = env('TELEGRAM_BOT_NAME', 'arigami_sage_bot');
        $deepLink = "https://t.me/{$botName}/direct?startapp=event_{$event->id}";

        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => 'Участие',
                        'url' => $deepLink
                    ]
                ]
            ]
        ];

        try {
            if ($event->telegram_message_id && $event->telegram_chat_id) {
                Telegram::editMessageText([
                    'chat_id' => $event->telegram_chat_id,
                    'message_id' => $event->telegram_message_id,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode($keyboard)
                ]);
            } else {
                $response = Telegram::sendMessage([
                    'chat_id' => $integration->platform_id,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode($keyboard)
                ]);
                $event->update([
                    'telegram_message_id' => $response->getMessageId(),
                    'telegram_chat_id' => $integration->platform_id
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Telegram Update Error: " . $e->getMessage());
        }
    }

    protected function updateDiscord(Event $event)
    {
        $botJsUrl = env('BOTJS_URL', 'http://localhost:3000') . '/update-event';

        try {
            Http::withHeaders([
                'X-Backend-Token' => config('services.discord.backend_api_secret')
            ])->post($botJsUrl, [
                'guild_id' => $event->guild->platform_id ?? null,
                'event_id' => $event->id,
                'message_id' => $event->discord_message_id ?? null,
                'event' => new EventFullResource($event)
            ]);
        } catch (\Exception $e) {
            Log::warning("Discord Update Skip: botJs not reachable. " . $e->getMessage());
        }
    }

    protected function formatTelegramMessage(Event $event): string
    {
        $isFirstTime = empty($event->telegram_message_id);

        $name = htmlspecialchars($event->name);
        $text = "<b>#{$event->id}: {$name}</b>\n";
        $text .= "⏰ <i>" . $event->start_at->format('Y-m-d H:i') . "</i>\n\n";

        if (!empty($event->description)) {
            $text .= "<b>📋 Описание</b>\n";
            $text .= htmlspecialchars($event->description) . "\n\n";
        }

        foreach ($event->squads as $squad) {
            $confirmedParticipants = $squad->participants->where('status', 'confirmed');
            $count = $confirmedParticipants->count();
            $limit = $squad->slots_limit;

            $squadTitle = htmlspecialchars($squad->title);
            $text .= "<b>🛡️ {$squadTitle} ({$count}/{$limit})</b>\n";
            $text .= "<pre>\n";

            if ($count > 0) {
                $participantLines = $confirmedParticipants->map(function($p) {
                    $familyName = $p->user->profile->family_name ?? null;
                    $globalName = $p->user->profile->global_name ?? null;
                    $userName = $p->user->name ?? 'Player';

                    if (!empty($familyName)) {
                        $displayName = $familyName . (!empty($globalName) ? " ({$globalName})" : "");
                    } elseif (!empty($globalName)) {
                        $displayName = $globalName;
                    } else {
                        $displayName = $userName;
                    }

                    return htmlspecialchars($displayName);
                })->implode("\n");
                $text .= $participantLines;
            } else {
                $text .= "Никто";
            }

            $text .= "\n</pre>\n";
        }

        // One-time ping logic
        if ($isFirstTime && !empty($event->notification_settings['roles'])) {
            $text .= "\n";
            $pings = [];
            foreach ($event->notification_settings['roles'] as $role) {
                $pings[] = "@" . htmlspecialchars($role);
            }
            $text .= implode(' ', $pings);
        }

        return $text;
    }
}
