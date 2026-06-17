<?php

namespace App\Services;

use App\MobileDeviceToken;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChatService
{
    public function __construct(private FirebaseAdminService $firebase)
    {
    }

    public function listThreadsForUser(User $user): array
    {
        return collect($this->firebase->listThreadsForUser((string) $user->id))
            ->map(fn($thread) => $this->formatThread($thread))
            ->values()
            ->all();
    }

    public function createOrGetThread(User $user, array $data): array
    {
        $participantIds = $this->normalizeParticipantIds($data['participantIds']);
        $contextType = $data['contextType'];
        $contextId = $data['contextId'] ?? null;

        $this->assertBusinessAccess($user, $participantIds, $contextType, $contextId);

        $threadId = $this->threadId($participantIds, $contextType, $contextId);
        $existing = $this->firebase->findThread($threadId);

        if ($existing) {
            return $this->formatThread($existing);
        }

        $thread = $this->firebase->upsertThread($threadId, [
            'participantIds' => $participantIds,
            'participantNames' => $this->participantNames($participantIds, $data['participantNames'] ?? []),
            'contextType' => $contextType,
            'contextId' => $contextId,
            'threadKey' => $this->threadKey($participantIds, $contextType, $contextId),
            'lastMessageText' => '',
            'updatedAt' => Carbon::now(),
        ]);

        return $this->formatThread($thread);
    }

    public function sendMessage(User $sender, string $threadId, string $text): array
    {
        $thread = $this->firebase->findThread($threadId);
        if (!$thread) {
            throw ValidationException::withMessages(['threadId' => 'The chat thread does not exist.']);
        }

        $participantIds = collect($thread['participantIds'] ?? [])->map(fn($id) => (string) $id)->all();
        if (!in_array((string) $sender->id, $participantIds, true)) {
            throw ValidationException::withMessages(['threadId' => 'The authenticated user cannot access this chat thread.']);
        }

        $message = $this->firebase->createMessage($threadId, [
            'senderId' => (string) $sender->id,
            'senderName' => trim($sender->name . ' ' . $sender->lastname),
            'text' => $text,
            'createdAt' => Carbon::now(),
        ]);

        $this->firebase->upsertThread($threadId, [
            'lastMessageText' => $text,
            'updatedAt' => Carbon::now(),
        ]);

        $recipientIds = collect($participantIds)
            ->reject(fn($id) => $id === (string) $sender->id)
            ->values()
            ->all();

        $tokens = MobileDeviceToken::active()
            ->whereIn('user_id', $recipientIds)
            ->pluck('fcm_token')
            ->all();

        $push = $this->firebase->sendChatPush($tokens, $text, $threadId);

        return [
            'threadId' => $threadId,
            'messageId' => $message['id'] ?? null,
            'message' => $message,
            'push' => $push,
        ];
    }

    private function normalizeParticipantIds(array $participantIds): array
    {
        $ids = collect($participantIds)
            ->map(fn($id) => trim((string) $id))
            ->filter()
            ->unique()
            ->values()
            ->all();

        sort($ids, SORT_STRING);

        return $ids;
    }

    private function assertBusinessAccess(User $user, array $participantIds, string $contextType, ?string $contextId): void
    {
        if (!in_array((string) $user->id, $participantIds, true)) {
            throw ValidationException::withMessages(['participantIds' => 'The authenticated user must be a chat participant.']);
        }

        $existingUsers = User::whereIn('id', $participantIds)->count();
        if ($existingUsers !== count($participantIds)) {
            throw ValidationException::withMessages(['participantIds' => 'All participants must be existing backend users.']);
        }

        if ($contextType === 'match' && $contextId && !DB::table('partidos')->where('id', $contextId)->exists()) {
            throw ValidationException::withMessages(['contextId' => 'The match context does not exist.']);
        }
    }

    private function participantNames(array $participantIds, array $providedNames): array
    {
        $users = User::whereIn('id', $participantIds)->get()->keyBy(fn($user) => (string) $user->id);

        return collect($participantIds)
            ->mapWithKeys(function ($id) use ($providedNames, $users) {
                $fallback = $users->has($id)
                    ? trim($users[$id]->name . ' ' . $users[$id]->lastname)
                    : $id;

                return [$id => $providedNames[$id] ?? $fallback];
            })
            ->all();
    }

    private function threadId(array $participantIds, string $contextType, ?string $contextId): string
    {
        return 'thread_' . sha1($this->threadKey($participantIds, $contextType, $contextId));
    }

    private function threadKey(array $participantIds, string $contextType, ?string $contextId): string
    {
        return $contextType . '|' . ($contextId ?? '') . '|' . implode('|', $participantIds);
    }

    private function formatThread(array $thread): array
    {
        $thread['threadId'] = $thread['id'] ?? null;
        unset($thread['name']);

        return $thread;
    }
}
