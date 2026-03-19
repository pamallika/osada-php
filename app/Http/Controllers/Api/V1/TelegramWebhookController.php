<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Guild;
use App\Models\GuildIntegration;
use App\Models\LinkedAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;
use Throwable;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request)
    {
        try {
            $update = Telegram::commandsHandler(true);
            
            // If commandsHandler didn't find a command, or we want to handle it manually:
            $message = $update->getMessage();
            if (!$message) {
                return response()->json(['status' => 'ok']);
            }

            $chatId = $message->getChat()->getId();
            $text = $message->getText();
            $telegramId = $message->getFrom()->getId();

            if (str_starts_with($text, '/start')) {
                $this->handleStart($text, $telegramId, $chatId, $message);
                return response()->json(['status' => 'ok']);
            } elseif (str_starts_with($text, '/bind')) {
                $this->handleBind($text, $telegramId, $chatId, $message);
                return response()->json(['status' => 'ok']);
            }
        } catch (Throwable $e) {
            Log::error('Telegram Webhook Critical Error: ' . $e->getMessage(), [
                'exception' => $e,
                'payload' => $request->all()
            ]);
        }

        return response()->json(['status' => 'ok']);
    }

    protected function handleStart(string $text, int $telegramId, int $chatId, $message)
    {
        $parts = explode(' ', $text);
        if (count($parts) < 2) {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => 'Welcome to SAGE! To bind your account, use the link from the website.'
            ]);
            return;
        }

        $token = $parts[1];

        // NEW LOGIC: Deep Link Auth
        if (str_starts_with($token, 'auth_')) {
            $code = substr($token, 5);
            $data = Cache::get('telegram_auth_code_' . $code);

            if (!$data || !is_array($data) || $data['status'] !== 'pending') {
                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => '⚠️ Invalid or expired auth code.'
                ]);
                return;
            }

            $from = $message->getFrom();
            $username = $from->getUsername();
            $firstName = $from->getFirstName();
            $lastName = $from->getLastName();
            $displayName = trim($firstName . ' ' . $lastName);
            $photoUrl = $this->getTelegramAvatar($telegramId);

            /** @var \App\Actions\Auth\AuthenticateTelegramAction $action */
            $action = app(\App\Actions\Auth\AuthenticateTelegramAction::class);
            $user = $action->findOrCreateUser($telegramId, $username, $displayName, $photoUrl);

            // Link user to auth code but keep verifier_hash for PKCE
            $data['status'] = $user->id;
            Cache::put('telegram_auth_code_' . $code, $data, now()->addMinutes(10));

            $callbackUrl = config('app.frontend_url') . '/auth/callback?auth_code=' . $code;

            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => "✅ Вы успешно авторизованы в SAGE!\n\nНажмите кнопку ниже, чтобы вернуться в приложение.",
                'reply_markup' => json_encode([
                    'inline_keyboard' => [[
                        ['text' => '🛡️ ВОЙТИ В SAGE', 'url' => $callbackUrl]
                    ]]
                ])
            ]);
            return;
        }

        // EXISTING LOGIC: Linking existing account
        $userId = Cache::get('telegram_token_' . $token);

        if (!$userId) {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => 'Invalid or expired token.'
            ]);
            return;
        }

        $user = User::find($userId);
        if ($user) {
            // Check if this Telegram ID is already linked to another user
            $existing = LinkedAccount::where('provider', 'telegram')
                ->where('provider_id', $telegramId)
                ->first();

            if ($existing && $existing->user_id !== $user->id) {
                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => '⚠️ Этот аккаунт Telegram уже привязан к другому пользователю SAGE. Сначала отвяжите его в профиле того аккаунта.'
                ]);
                return;
            }

            $from = $message->getFrom();
            $username = $from->getUsername();
            $firstName = $from->getFirstName();
            $lastName = $from->getLastName();
            $displayName = trim($firstName . ' ' . $lastName);
            
            $avatarUrl = $this->getTelegramAvatar($telegramId);

            $user->linkedAccounts()->updateOrCreate(
                ['provider' => 'telegram'],
                [
                    'provider_id' => (string)$telegramId,
                    'username' => $username,
                    'display_name' => $displayName ?: ($username ?: $user->name),
                    'avatar' => $avatarUrl,
                ]
            );

            // Актуализация Global Name, если пусто
            if (!$user->profile || empty($user->profile->global_name)) {
                $user->profile()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'global_name' => $displayName ?: ($username ?: $user->name),
                        'family_name' => $user->profile?->family_name ?? '',
                        'char_class' => $user->profile?->char_class ?? 'None',
                    ]
                );
            }

            Cache::forget('telegram_token_' . $token);

            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => 'Account successfully bound to SAGE!'
            ]);
        }
    }

    protected function getTelegramAvatar(int $telegramId): ?string
    {
        try {
            $photos = Telegram::getUserProfilePhotos(['user_id' => $telegramId, 'limit' => 1]);

            if ($photos && $photos->getTotalCount() > 0) {
                $allPhotos = $photos->getPhotos();
                $firstPhotoSet = $allPhotos[0];

                // Get the largest size
                $largestPhoto = collect($firstPhotoSet)->sortByDesc('file_size')->first();
                $fileId = $largestPhoto->getFileId();

                $file = Telegram::getFile(['file_id' => $fileId]);
                $filePath = $file->getFilePath();

                $bot = config('telegram.default');
                $token = config("telegram.bots.{$bot}.token");

                return "https://api.telegram.org/file/bot{$token}/{$filePath}";
            }
        } catch (Throwable $e) {
            Log::error('Telegram Avatar Fetch Error: ' . $e->getMessage());
        }

        return null;
    }

    protected function handleBind(string $text, int $telegramId, int $chatId, $message)
    {
        $parts = explode(' ', $text);
        if (count($parts) < 2) {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => 'Usage: /bind {token}'
            ]);
            return;
        }

        $token = $parts[1];
        $guildId = Cache::get('guild_telegram_bind_token_' . $token);

        if (!$guildId) {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => 'Invalid or expired binding token.'
            ]);
            return;
        }

        // Check if the user who sent the command is an admin in this guild
        $linkedAccount = LinkedAccount::where('provider', 'telegram')
            ->where('provider_id', $telegramId)
            ->first();

        if (!$linkedAccount) {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => 'Please bind your personal Telegram account first via /start on the website.'
            ]);
            return;
        }

        $user = $linkedAccount->user;
        $membership = $user->guildMemberships()
            ->where('guild_id', $guildId)
            ->whereIn('role', ['admin', 'creator'])
            ->where('status', 'active')
            ->first();

        if (!$membership) {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => 'You do not have enough permissions in SAGE to bind this group.'
            ]);
            return;
        }

        $guild = Guild::find($guildId);
        if ($guild) {
            $chat = $message->getChat();
            $chatType = $chat->getType();

            if (!in_array($chatType, ['group', 'supergroup', 'channel'])) {
                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => '⚠️ Привязка доступна только для групп или каналов.'
                ]);
                return;
            }

            $platformTitle = $chat->getTitle() ?: $chat->getUsername();

            // Check if this chat is already bound to another guild
            $existingBind = GuildIntegration::where('provider', 'telegram')
                ->where('platform_id', $chatId)
                ->first();

            if ($existingBind && $existingBind->guild_id !== $guild->id) {
                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => '⚠️ Эта группа уже привязана к гильдии в SAGE. Чтобы перепривязать её, сначала удалите старую интеграцию в панели управления SAGE.'
                ]);
                return;
            }

            GuildIntegration::updateOrCreate(
                ['guild_id' => $guild->id, 'provider' => 'telegram'],
                [
                    'platform_id' => (string)$chatId,
                    'platform_title' => $platformTitle,
                ]
            );

            Cache::forget('guild_telegram_bind_token_' . $token);

            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => "Group successfully bound to guild: {$guild->name}"
            ]);
        }
    }
}
