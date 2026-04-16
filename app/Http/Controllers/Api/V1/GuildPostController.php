<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Guilds\UploadPostMediaAction;
use App\Actions\Guilds\ReorderPostsAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\GuildPostResource;
use App\Models\GuildMember;
use App\Models\GuildPost;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GuildPostController extends Controller
{
    use ApiResponser;

    /**
     * Display a listing of guild posts.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $membership = GuildMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail();

        $posts = GuildPost::where('guild_id', $membership->guild_id)
            ->with(['author.profile'])
            ->orderBy('position', 'asc')
            ->latest()
            ->get();

        return $this->successResponse(GuildPostResource::collection($posts));
    }

    /**
     * Store a new guild post.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $membership = GuildMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail();

        Gate::authorize('managePosts', $membership->guild);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $maxPosition = GuildPost::where('guild_id', $membership->guild_id)->max('position') ?? 0;

        $post = GuildPost::create([
            'guild_id' => $membership->guild_id,
            'author_id' => $user->id,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'position' => $maxPosition + 1,
        ]);

        return $this->successResponse(new GuildPostResource($post->load('author.profile')), 'Post created', 201);
    }

    /**
     * Display the specified guild post.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        $membership = GuildMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail();

        $post = GuildPost::where('guild_id', $membership->guild_id)
            ->with(['author.profile'])
            ->findOrFail($id);

        return $this->successResponse(new GuildPostResource($post));
    }

    /**
     * Update the specified guild post.
     */
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        $membership = GuildMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail();

        Gate::authorize('managePosts', $membership->guild);

        $post = GuildPost::where('guild_id', $membership->guild_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
        ]);

        $post->update($validated);

        return $this->successResponse(new GuildPostResource($post->load('author.profile')), 'Post updated');
    }

    /**
     * Remove the specified guild post from storage.
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        $membership = GuildMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail();

        Gate::authorize('managePosts', $membership->guild);

        $post = GuildPost::where('guild_id', $membership->guild_id)
            ->findOrFail($id);

        $post->delete();

        return $this->successResponse(null, 'Post deleted');
    }

    /**
     * Upload media for post content.
     */
    public function uploadMedia(Request $request, UploadPostMediaAction $action): JsonResponse
    {
        $user = $request->user();
        $membership = GuildMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail();

        Gate::authorize('managePosts', $membership->guild);

        $request->validate([
            'image' => 'required|image|max:3072|mimes:jpeg,png,webp',
        ]);

        $url = $action->execute($request->file('image'), $membership->guild_id);

        return $this->successResponse(['url' => $url], 'Image uploaded');
    }

    /**
     * Reorder posts for the guild.
     */
    public function reorder(Request $request, ReorderPostsAction $action): JsonResponse
    {
        $user = $request->user();
        $membership = GuildMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail();

        Gate::authorize('managePosts', $membership->guild);

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:guild_posts,id',
        ]);

        $action->execute($membership->guild, $validated['ids']);

        $posts = GuildPost::where('guild_id', $membership->guild_id)
            ->with(['author.profile'])
            ->orderBy('position', 'asc')
            ->latest()
            ->get();

        return $this->successResponse(GuildPostResource::collection($posts), 'Posts reordered');
    }
}
