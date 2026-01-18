<?php

namespace App\Actions\Events\Core;

use App\Models\Event;

class UpdateEventStatusAction
{
    public function execute(Event $event, string $status): Event
    {
        $event->update(['status' => $status]);
        return $event;
    }
}
