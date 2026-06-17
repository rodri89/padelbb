<?php

namespace Tests\Feature;

use App\Services\ChatService;
use App\Services\FirebaseAdminService;
use App\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;
use Mockery;
use Tests\TestCase;

class PadelMatchMobileApiTest extends TestCase
{
    public function testFirebaseTokenEndpointReturnsCustomToken()
    {
        $user = $this->actingApiUser();

        $this->mock(FirebaseAdminService::class, function ($mock) use ($user) {
            $mock->shouldReceive('createCustomToken')
                ->once()
                ->with((string) $user->id)
                ->andReturn('firebase-custom-token');
        });

        $response = $this->jsonKernelRequest('POST', '/api/mobile/firebase-token');

        $response->assertOk()
            ->assertJson(['firebaseToken' => 'firebase-custom-token']);
    }

    public function testCreateChatThreadReturnsThreadId()
    {
        $user = $this->actingApiUser();

        $this->mock(ChatService::class, function ($mock) use ($user) {
            $mock->shouldReceive('createOrGetThread')
                ->once()
                ->with(Mockery::on(fn($authUser) => (string) $authUser->id === (string) $user->id), Mockery::type('array'))
                ->andReturn([
                    'threadId' => 'thread_123',
                    'participantIds' => ['1', '2'],
                ]);
        });

        $response = $this->jsonKernelRequest('POST', '/api/chats/threads', [
            'participantIds' => ['1', '2'],
            'participantNames' => ['1' => 'User One', '2' => 'User Two'],
            'contextType' => 'direct',
        ]);

        $response->assertOk()
            ->assertJsonPath('threadId', 'thread_123');
    }

    public function testSendChatMessageUsesBackendFlow()
    {
        $user = $this->actingApiUser();

        $this->mock(ChatService::class, function ($mock) use ($user) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->with(Mockery::on(fn($authUser) => (string) $authUser->id === (string) $user->id), 'thread_123', 'Hola')
                ->andReturn([
                    'threadId' => 'thread_123',
                    'messageId' => 'message_123',
                    'push' => ['successCount' => 1, 'failureCount' => 0, 'revokedTokens' => []],
                ]);
        });

        $response = $this->jsonKernelRequest('POST', '/api/chats/threads/thread_123/messages', [
            'text' => 'Hola',
        ]);

        $response->assertCreated()
            ->assertJsonPath('messageId', 'message_123')
            ->assertJsonPath('push.successCount', 1);
    }

    private function actingApiUser(): User
    {
        $user = new User([
            'name' => 'User',
            'lastname' => 'One',
            'email' => 'user@example.com',
        ]);
        $user->id = 1;

        Passport::actingAs($user, [], 'api');

        return $user;
    }

    private function jsonKernelRequest(string $method, string $uri, array $data = []): TestResponse
    {
        $request = Request::create(
            $uri,
            $method,
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode($data)
        );

        return TestResponse::fromBaseResponse($this->app->make(Kernel::class)->handle($request));
    }
}
