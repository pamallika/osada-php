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
            if (isset($data['guild_id'])) {
                $guild = Guild::query()->findOrFail($data['guild_id']);
            } else {
                $guildId = \App\Models\GuildIntegration::where('provider', 'discord')
                    ->where('platform_id', $data['discord_guild_id'])
                    ->value('guild_id');
                
                if (!$guildId) {
                    throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Guild integration not found');
                }
                $guild = Guild::query()->findOrFail($guildId);
            }

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
                'start_at' => \Illuminate\Support\Carbon::parse($data['start_at'])->setTimezone('UTC'),
                'total_slots' => $totalSlots, // Используем посчитанное или нулевое значение
                'is_free_registration' => $data['is_free_registration'] ?? false,
                'status' => 'draft',
            ]);

            // 3. Автоматическое создание Резерва (Системный отряд)
            $event->squads()->create([
                'title' => 'Резерв',
                'slots_limit' => 0,
                'position' => -1, // В самое начало или конец? Обычно резерв в конце или начале.
                'is_system' => true,
            ]);

            // 4. Создание отрядов, только если они были переданы
            if (!empty($squads)) {
                foreach ($squads as $index => $squad) {
                    $event->squads()->create([
                        'title' => $squad['name'], // Используем 'title' как в БД
                        'slots_limit' => $squad['limit'], // Используем 'limit'
                        'position' => $index,
                    ]);
                }
            }

            return $event;
        });
    }
}
