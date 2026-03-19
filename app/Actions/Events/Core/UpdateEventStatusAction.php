<?php

namespace App\Actions\Events\Core;

use App\Models\Event;
use App\Models\EventParticipant;
use Illuminate\Support\Facades\DB;

class UpdateEventStatusAction
{
    public function execute(Event $event, string $status, ?array $notificationSettings = null): Event
    {
        return DB::transaction(function () use ($event, $status, $notificationSettings) {
            $oldStatus = $event->status;
            
            $updateData = ['status' => $status];
            if ($notificationSettings) {
                $updateData['notification_settings'] = $notificationSettings;
            }
            
            $event->update($updateData);

            // Если событие переходит в статус published из любого другого статуса (обычно из draft)
            if ($status === 'published' && $oldStatus !== 'published') {
                $activeMemberUserIds = $event->guild->members()
                    ->where('status', 'active')
                    ->pluck('user_id');

                foreach ($activeMemberUserIds as $userId) {
                    EventParticipant::query()->firstOrCreate(
                        [
                            'event_id' => $event->id,
                            'user_id' => $userId,
                        ],
                        [
                            'status' => 'pending',
                        ]
                    );
                }
            }

            // Если есть изменения в статусе или настройках уведомлений
            if ($status === 'published' || $notificationSettings) {
                \App\Jobs\UpdateMessengerEventMessage::dispatch($event->id)->delay(now()->addSeconds(5));
            }

            return $event;
        });
    }
}
