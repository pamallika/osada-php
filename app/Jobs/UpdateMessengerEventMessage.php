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
            Http::post($botJsUrl, [
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
        $text = "<b>{$name}</b>\n";
        $text .= "Time: " . $event->start_at->format('Y-m-d H:i') . "\n";
        $text .= "Status: " . ucfirst($event->status) . "\n\n";

        foreach ($event->squads as $squad) {
            $confirmedParticipants = $squad->participants->where('status', 'confirmed');
            $count = $confirmedParticipants->count();

            $nicknames = $confirmedParticipants->map(function($p) {
                $displayName = !empty($p->user->profile->family_name)
                    ? $p->user->profile->family_name
                    : ($p->user->profile->global_name ?? 'Player');
                return htmlspecialchars($displayName);
            })->implode(', ');

            $squadTitle = htmlspecialchars($squad->title);
            $text .= "🛡️ {$squadTitle} ({$count}/{$squad->slots_limit})";
            if ($count > 0) {
                $text .= ": {$nicknames}";
            }
            $text .= "\n";
        }

        $text .= "\nTotal Slots: {$event->total_slots}";

        // One-time ping logic
        if ($isFirstTime && !empty($event->notification_settings['roles'])) {
            $text .= "\n\n";
            $pings = [];
            foreach ($event->notification_settings['roles'] as $role) {
                // In Telegram, there's no direct "role mention" by ID like in Discord for arbitrary bots usually,
                // but we can assume these are some identifiers or we just list them if they are names.
                // However, requirement says "Include role mentions".
                // For Telegram, role mentions are usually just text or special tags if supported.
                // If they are discord role IDs, they won't work in TG.
                // If they are telegram group/topic identifiers, maybe.
                // Given the context of a SaaS for BDO guilds, these might be internal role names or custom tags.
                $pings[] = "@" . htmlspecialchars($role);
            }
            $text .= implode(' ', $pings);
        }

        return $text;
    }
}
