<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Actions\Guilds\CreateGuildAction;
use Illuminate\Http\Request;

class GuildController extends Controller
{
    public function store(Request $request, CreateGuildAction $action)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:50',
            'logo_url' => 'nullable|url',
        ]);

        $guild = $action->execute($request->user(), $validated);

        return response()->json([
            'message' => 'Guild created successfully',
            'data' => $guild
        ], 201);
    }
}
