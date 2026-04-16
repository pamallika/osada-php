<?php

namespace App\Actions\Guilds;

use App\Models\Guild;
use Illuminate\Support\Facades\DB;

class ReorderPostsAction
{
    /**
     * Reorder posts for a given guild.
     *
     * @param Guild $guild
     * @param array $ids Array of post IDs in the desired order.
     * @return void
     */
    public function execute(Guild $guild, array $ids): void
    {
        DB::transaction(function () use ($guild, $ids) {
            foreach ($ids as $index => $id) {
                $guild->posts()
                    ->where('id', $id)
                    ->update(['position' => $index]);
            }
        });
    }
}
