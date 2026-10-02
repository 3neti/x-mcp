<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use LBHurtado\XMcp\Servers\XChangePublicIssuanceServer;
use LBHurtado\XMcp\Tools\DiscoverOnDemandIssuanceTool;
use LBHurtado\XMcp\Tools\EstimateOnDemandPayCodeTool;
use LBHurtado\XMcp\Tools\PrepareOnDemandHandoffTool;

beforeEach(function (): void {
    Http::preventStrayRequests();
});

it('discovers public issuance anonymously through its read-only api', function (): void {
    Http::fake([
        'https://public.example.test/api/x/v1/public-issuance' => Http::response([
            'schema' => 'x-change.public-issuance-discovery.v1',
            'available' => true,
            'creates_order' => false,
        ]),
    ]);

    XChangePublicIssuanceServer::tool(DiscoverOnDemandIssuanceTool::class)
        ->assertOk()
        ->assertSee('x-change.public-issuance-discovery.v1');

    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
        && ! $request->hasHeader('Authorization'));
});

it('estimates without creating an order or sending credentials', function (): void {
    Http::fake([
        'https://public.example.test/api/x/v1/public-issuance/estimate' => Http::response([
            'schema' => 'x-change.public-issuance-estimate.v1',
            'principal_minor' => 2500,
            'total_required_minor' => 4000,
            'creates_order' => false,
        ]),
    ]);

    XChangePublicIssuanceServer::tool(EstimateOnDemandPayCodeTool::class, [
        'amount_minor' => 2500,
        'currency' => 'PHP',
    ])->assertOk()->assertSee('4000');

    Http::assertSent(fn (Request $request): bool => $request->data() === [
        'amount_minor' => 2500,
        'currency' => 'PHP',
    ] && ! $request->hasHeader('Authorization'));
});

it('prepares a browser handoff without exposing a possession token', function (): void {
    Http::fake([
        'https://public.example.test/api/x/v1/public-issuance/handoff' => Http::response([
            'schema' => 'x-change.public-issuance-handoff.v1',
            'url' => 'https://public.example.test/x/auto-generate?amount=25.00&currency=PHP',
            'creates_order' => false,
        ]),
    ]);

    XChangePublicIssuanceServer::tool(PrepareOnDemandHandoffTool::class, [
        'amount_minor' => 2500,
        'currency' => 'PHP',
    ])->assertOk()
        ->assertSee('/x/auto-generate')
        ->assertDontSee('token');
});

it('publishes separate public and institutional discovery documents', function (): void {
    $this->getJson('/.well-known/x-change-public-mcp')
        ->assertOk()
        ->assertJsonPath('authentication.type', 'none')
        ->assertJsonPath('available', true)
        ->assertJsonPath('read_only', true)
        ->assertJsonCount(3, 'tools');

    $this->getJson('/.well-known/x-change-mcp')
        ->assertOk()
        ->assertJsonPath('authentication.type', 'oauth2_client_credentials_bearer');
});

it('registers the public MCP transport without bearer authentication', function (): void {
    $response = $this->postJson('/mcp/x-change/public', []);

    expect($response->status())->not->toBeIn([401, 404]);
});

it('advertises exact safety annotations for every public tool', function (string $tool): void {
    $reflection = new ReflectionClass($tool);

    expect($reflection->getAttributes(IsReadOnly::class)[0]->newInstance()->value)->toBeTrue()
        ->and($reflection->getAttributes(IsDestructive::class)[0]->newInstance()->value)->toBeFalse()
        ->and($reflection->getAttributes(IsIdempotent::class)[0]->newInstance()->value)->toBeTrue()
        ->and($reflection->getAttributes(IsOpenWorld::class)[0]->newInstance()->value)->toBeFalse();
})->with([
    DiscoverOnDemandIssuanceTool::class,
    EstimateOnDemandPayCodeTool::class,
    PrepareOnDemandHandoffTool::class,
]);
