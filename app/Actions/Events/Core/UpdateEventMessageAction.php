<?php

namespace App\Actions\Events\Core;

use App\Models\Event;

class UpdateEventMessageAction
{
    public function execute(Event $event, ?string $messageId): Event
    {
        $event->update(['discord_message_id' => $messageId]);
        return $event;
    }
}
