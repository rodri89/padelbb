# PadelMatch Mobile API

All endpoints are served by Laravel under the existing API prefix, so the public paths start with `/api`.

## Environment

Configure Firebase with environment variables only. Do not commit service account JSON files.

```dotenv
FIREBASE_PROJECT_ID=
FIREBASE_CLIENT_EMAIL=
FIREBASE_PRIVATE_KEY=
FIREBASE_DATABASE_URL=
APP_PUBLIC_URL=https://padelbb.com
```

`FIREBASE_PRIVATE_KEY` can be stored with escaped line breaks (`\n`).

## Manual Checks

### Login

```bash
curl -X POST https://padelbb.com/api/auth/login \
  -H 'Accept: application/json' \
  -d 'email=user@example.com' \
  -d 'password=secret'
```

Expected response includes `token`, `userId`, `displayName`, `access_token`, `token_type` and `expires_at`.

### Register FCM Token

```bash
curl -X POST https://padelbb.com/api/mobile/devices/register \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{"userId":"1","fcmToken":"FCM_TOKEN","platform":"android","deviceId":"device-123"}'
```

Expected result: token is upserted in `mobile_device_tokens`, `last_seen_at` is updated, and `revoked_at` is cleared.

### Unregister FCM Token

```bash
curl -X POST https://padelbb.com/api/mobile/devices/unregister \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{"userId":"1","fcmToken":"FCM_TOKEN","platform":"android"}'
```

Expected result: `revoked` is true when a matching token was found, and `revoked_at` is set.

### Firebase Custom Token

```bash
curl -X POST https://padelbb.com/api/mobile/firebase-token \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer TOKEN'
```

Expected response includes `firebaseToken`. The React Native app should sign in to Firebase with this custom token.

### Create Or Get Chat Thread

```bash
curl -X POST https://padelbb.com/api/chats/threads \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{"participantIds":["1","2"],"participantNames":{"1":"User One","2":"User Two"},"contextType":"direct"}'
```

Expected result: response includes `threadId`, and Firestore contains `chatThreads/{threadId}` with `participantIds`, `participantNames`, `contextType`, `lastMessageText` and `updatedAt`.

### Send Message And Push

```bash
curl -X POST https://padelbb.com/api/chats/threads/THREAD_ID/messages \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{"text":"Hola, nos vemos en la cancha"}'
```

Expected result: Laravel writes a message under `chatThreads/{threadId}/messages`, updates the thread's `lastMessageText`, sends FCM to other active participants, and revokes invalid FCM tokens returned by Firebase.
