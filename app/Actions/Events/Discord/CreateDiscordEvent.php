<?php

namespace App\Actions\Events\Discord;

use App\Models\Event;
use App\Models\Guild;
use Illuminate\Support\Facades\DB;

class CreateDiscordEvent
{
    public function execute(array $data): Event
    {
        return DB::transaction(function () use ($data) {
            $guild = Guild::query()->where('discord_id', $data['discord_guild_id'])->firstOrFail();

            // 1. Подготовка данных
            $squads = $data['squads'] ?? []; // Если отряды не пришли, используем пустой массив
            $totalSlots = 0;

            // Если отряды есть, считаем общее количество слотов
            if (!empty($squads)) {
                $totalSlots = array_reduce($squads, function ($carry, $squad) {
                    return $carry + $squad['limit']; // Используем 'limit' как в новом фронтенде
                }, 0);
            }

            // 2. Создание события
            $event = Event::query()->create([
                'guild_id' => $guild->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'region' => $data['region'],
                'start_at' => $data['start_at'],
                'total_slots' => $totalSlots, // Используем посчитанное или нулевое значение
                'is_free_registration' => $data['is_free_registration'] ?? false,
                'status' => 'draft',
            ]);

            // 3. Создание отрядов, только если они были переданы
            if (!empty($squads)) {
                foreach ($squads as $index => $squad) {
                    $event->squads()->create([
                        'name' => $squad['name'], // Используем 'name' как в новом фронтенде
                        'slots_limit' => $squad['limit'], // Используем 'limit'
                        'position' => $index,
                    ]);
                }
            }

            return $event;
        });
    }
}
