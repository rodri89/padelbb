<?php

namespace App\Services;

use App\MobileDeviceToken;
use Carbon\Carbon;
use Google\Auth\Credentials\ServiceAccountCredentials;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use RuntimeException;

class FirebaseAdminService
{
    private ?Client $firestoreClient = null;
    private ?string $accessToken = null;

    public function createCustomToken(string $uid): string
    {
        return (string) $this->factory()->createAuth()->createCustomToken($uid);
    }

    public function findThread(string $threadId): ?array
    {
        $document = $this->getDocument('chatThreads/' . $threadId);

        return $document ? $this->decodeDocument($document) : null;
    }

    public function upsertThread(string $threadId, array $data): array
    {
        $document = $this->patchDocument('chatThreads/' . $threadId, $data);

        return $this->decodeDocument($document);
    }

    public function listThreadsForUser(string $userId): array
    {
        $response = $this->request('POST', ':runQuery', [
            'structuredQuery' => [
                'from' => [
                    ['collectionId' => 'chatThreads'],
                ],
                'where' => [
                    'fieldFilter' => [
                        'field' => ['fieldPath' => 'participantIds'],
                        'op' => 'ARRAY_CONTAINS',
                        'value' => ['stringValue' => $userId],
                    ],
                ],
            ],
        ]);

        return collect($response)
            ->pluck('document')
            ->filter()
            ->map(fn($document) => $this->decodeDocument($document))
            ->sortByDesc(fn($thread) => $thread['updatedAt'] ?? '')
            ->values()
            ->all();
    }

    public function createMessage(string $threadId, array $data): array
    {
        $document = $this->request('POST', 'chatThreads/' . $threadId . '/messages', $data);

        return $this->decodeDocument($document);
    }

    public function sendChatPush(array $tokens, string $body, string $threadId): array
    {
        $tokens = array_values(array_unique(array_filter($tokens)));
        if (empty($tokens)) {
            return ['successCount' => 0, 'failureCount' => 0, 'revokedTokens' => []];
        }

        $message = CloudMessage::new()
            ->withNotification(Notification::create('Nuevo mensaje', $body))
            ->withData([
                'type' => 'chat_message',
                'threadId' => $threadId,
            ]);

        $report = $this->factory()->createMessaging()->sendMulticast($message, $tokens);
        $invalidTokens = array_values(array_unique(array_merge(
            $report->invalidTokens(),
            $report->unknownTokens()
        )));

        if (!empty($invalidTokens)) {
            MobileDeviceToken::whereIn('fcm_token', $invalidTokens)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => Carbon::now()]);
        }

        return [
            'successCount' => $report->successes()->count(),
            'failureCount' => $report->failures()->count(),
            'revokedTokens' => $invalidTokens,
        ];
    }

    private function factory(): Factory
    {
        $factory = (new Factory())
            ->withServiceAccount($this->serviceAccount())
            ->withProjectId($this->projectId());

        if (config('services.firebase.database_url')) {
            $factory = $factory->withDatabaseUri(config('services.firebase.database_url'));
        }

        return $factory;
    }

    private function getDocument(string $path): ?array
    {
        try {
            return $this->request('GET', $path);
        } catch (ClientException $exception) {
            if ($exception->getResponse() && $exception->getResponse()->getStatusCode() === 404) {
                return null;
            }

            throw $exception;
        }
    }

    private function patchDocument(string $path, array $data): array
    {
        $query = collect(array_keys($data))
            ->map(fn($field) => 'updateMask.fieldPaths=' . rawurlencode($field))
            ->implode('&');

        return $this->request('PATCH', $path . ($query ? '?' . $query : ''), $data);
    }

    private function request(string $method, string $path, ?array $data = null): array
    {
        $requestPath = $path === ':runQuery' ? '../documents:runQuery' : $path;
        $options = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken(),
                'Accept' => 'application/json',
            ],
        ];

        if ($data !== null) {
            $options['json'] = str_starts_with($path, ':runQuery')
                ? $data
                : ['fields' => $this->encodeFields($data)];
        }

        $response = $this->firestoreClient()->request($method, $requestPath, $options);

        return json_decode((string) $response->getBody(), true) ?: [];
    }

    private function firestoreClient(): Client
    {
        if ($this->firestoreClient === null) {
            $this->firestoreClient = new Client([
                'base_uri' => 'https://firestore.googleapis.com/v1/projects/' . $this->projectId() . '/databases/(default)/documents/',
                'timeout' => 15,
            ]);
        }

        return $this->firestoreClient;
    }

    private function accessToken(): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $credentials = new ServiceAccountCredentials(
            ['https://www.googleapis.com/auth/datastore'],
            $this->serviceAccount()
        );
        $token = $credentials->fetchAuthToken();

        if (empty($token['access_token'])) {
            throw new RuntimeException('Unable to fetch a Firebase service account access token.');
        }

        return $this->accessToken = $token['access_token'];
    }

    private function serviceAccount(): array
    {
        $projectId = $this->projectId();
        $clientEmail = config('services.firebase.client_email');
        $privateKey = config('services.firebase.private_key');

        if (!$clientEmail || !$privateKey) {
            throw new RuntimeException('Firebase service account environment variables are not configured.');
        }

        return [
            'type' => 'service_account',
            'project_id' => $projectId,
            'client_email' => $clientEmail,
            'private_key' => $privateKey,
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ];
    }

    private function projectId(): string
    {
        $projectId = config('services.firebase.project_id');
        if (!$projectId) {
            throw new RuntimeException('FIREBASE_PROJECT_ID is not configured.');
        }

        return $projectId;
    }

    private function encodeFields(array $data): array
    {
        return collect($data)
            ->map(fn($value) => $this->encodeValue($value))
            ->all();
    }

    private function encodeValue($value): array
    {
        if ($value instanceof Carbon) {
            return ['timestampValue' => $value->toJSON()];
        }

        if (is_array($value)) {
            if (array_is_list($value)) {
                return [
                    'arrayValue' => [
                        'values' => array_map(fn($item) => $this->encodeValue($item), $value),
                    ],
                ];
            }

            return ['mapValue' => ['fields' => $this->encodeFields($value)]];
        }

        if (is_bool($value)) {
            return ['booleanValue' => $value];
        }

        if (is_int($value)) {
            return ['integerValue' => (string) $value];
        }

        if ($value === null) {
            return ['nullValue' => null];
        }

        return ['stringValue' => (string) $value];
    }

    private function decodeDocument(array $document): array
    {
        $data = $this->decodeFields($document['fields'] ?? []);
        $data['id'] = basename($document['name'] ?? '');
        $data['name'] = $document['name'] ?? null;
        $data['createTime'] = $document['createTime'] ?? null;
        $data['updateTime'] = $document['updateTime'] ?? null;

        return $data;
    }

    private function decodeFields(array $fields): array
    {
        return collect($fields)
            ->map(fn($value) => $this->decodeValue($value))
            ->all();
    }

    private function decodeValue(array $value)
    {
        if (array_key_exists('stringValue', $value)) {
            return $value['stringValue'];
        }

        if (array_key_exists('integerValue', $value)) {
            return (int) $value['integerValue'];
        }

        if (array_key_exists('booleanValue', $value)) {
            return (bool) $value['booleanValue'];
        }

        if (array_key_exists('timestampValue', $value)) {
            return $value['timestampValue'];
        }

        if (array_key_exists('arrayValue', $value)) {
            return collect($value['arrayValue']['values'] ?? [])
                ->map(fn($item) => $this->decodeValue($item))
                ->all();
        }

        if (array_key_exists('mapValue', $value)) {
            return $this->decodeFields($value['mapValue']['fields'] ?? []);
        }

        return null;
    }
}
