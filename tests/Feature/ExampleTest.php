<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $response = $this->kernelRequest('GET', '/login');

        $response->assertStatus(200);
    }

    private function kernelRequest(string $method, string $uri): TestResponse
    {
        $request = Request::create($uri, $method);

        return TestResponse::fromBaseResponse($this->app->make(Kernel::class)->handle($request));
    }
}
