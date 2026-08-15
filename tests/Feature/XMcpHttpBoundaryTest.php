<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;

it('publishes sanitized discovery without enabling self-registration', function () {
    $this->getJson('/.well-known/x-change-mcp')
        ->assertSuccessful()
        ->assertJsonPath('schema', 'x-mcp.discovery.v1')
        ->assertJsonPath('authentication.self_service_registration', false)
        ->assertJsonMissingPath('client_secret');
});

it('requires and validates a Partner API bearer token before starting MCP', function () {
    $this->postJson('/mcp/x-change', [])->assertUnauthorized();

    Http::fake([
        'https://partner.example.test/api/partner/v1/capabilities' => Http::response([
            'success' => false,
            'code' => 'invalid_token',
            'message' => 'Invalid token.',
        ], 401),
    ]);

    $this->withToken('invalid-token')->postJson('/mcp/x-change', [])->assertUnauthorized();
});

it('fails closed when the Partner API contract version drifts', function () {
    Http::fake([
        'https://partner.example.test/api/partner/v1/capabilities' => Http::response([
            'success' => true,
            'data' => [
                'contract' => ['version' => '2.0.0', 'sha256' => 'changed'],
                'operations' => ['capabilities:read'],
            ],
        ]),
    ]);

    $this->withToken('partner-token')->postJson('/mcp/x-change', [])->assertServerError();
});
