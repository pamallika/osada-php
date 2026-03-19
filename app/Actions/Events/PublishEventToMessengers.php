<?php

namespace App\Actions\Events;

use App\Models\Event;
use App\Jobs\UpdateMessengerEventMessage;

class PublishEventToMessengers
{
    public function execute(Event $event)
    {
        // При публикации мы просто диспатчим джобу, 
        // которая создаст первое сообщение или обновит его.
        // Используем задержку для дебаунса.
        
        UpdateMessengerEventMessage::dispatch($event->id)->delay(now()->addSeconds(5));
        
        return true;
    }
}
