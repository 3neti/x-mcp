<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use LBHurtado\XMcp\Servers\XChangePartnerServer;
use LBHurtado\XMcp\Services\PartnerBearerTokenContext;
use LBHurtado\XMcp\Tools\CancelPayCodeTool;
use LBHurtado\XMcp\Tools\CreateFundedOrderTool;
use LBHurtado\XMcp\Tools\EstimatePayCodeTool;
use LBHurtado\XMcp\Tools\GetFundedOrderTool;
use LBHurtado\XMcp\Tools\GetPayCodeTool;
use LBHurtado\XMcp\Tools\InspectCapabilitiesTool;
use LBHurtado\XMcp\Tools\IssuePayCodeTool;

function authenticateMcpContext(array $scopes): void
{
    app(PartnerBearerTokenContext::class)->set('partner-token', [
        'data' => ['operations' => $scopes],
    ]);
}

function mcpPayCode(): array
{
    return [
        'cash' => [
            'amount' => 50.00,
            'currency' => 'PHP',
            'settlement_rail' => 'INSTAPAY',
            'validation' => ['mobile' => '09171234567'],
        ],
        'inputs' => ['fields' => ['name']],
        'feedback' => ['email' => null, 'mobile' => null, 'webhook' => null],
        'rider' => ['message' => 'Test', 'url' => null, 'splash' => null],
        'count' => 1,
    ];
}

beforeEach(function () {
    Http::preventStrayRequests();
});

it('inspects capabilities over HTTP with the request bearer token', function () {
    authenticateMcpContext(['capabilities:read']);
    Http::fake([
        'https://partner.example.test/api/partner/v1/capabilities' => Http::response([
            'success' => true,
            'data' => ['schema' => 'x-change.partner-capabilities.v1'],
        ]),
    ]);

    XChangePartnerServer::tool(InspectCapabilitiesTool::class)
        ->assertOk()
        ->assertSee('x-change.partner-capabilities.v1');

    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
        && $request->hasHeader('Authorization', 'Bearer partner-token'));
});

it('estimates by forwarding the exact Pay Code contract', function () {
    authenticateMcpContext(['pay-codes:estimate']);
    Http::fake([
        'https://partner.example.test/api/partner/v1/pay-code-estimates' => Http::response([
            'success' => true,
            'data' => ['currency' => 'PHP', 'account_debit' => 65.50],
        ]),
    ]);

    XChangePartnerServer::tool(EstimatePayCodeTool::class, ['pay_code' => mcpPayCode()])
        ->assertOk()
        ->assertSee('65.5');

    Http::assertSent(fn (Request $request): bool => data_get($request->data(), 'cash.amount') === 50.0
        && data_get($request->data(), 'cash.validation.mobile') === '09171234567'
        && data_get($request->data(), 'inputs.fields') === ['name']);
});

it('requires confirmation and forwards stable mutation headers for issuance', function () {
    authenticateMcpContext(['pay-codes:issue']);
    Http::fake([
        'https://partner.example.test/api/partner/v1/pay-codes' => Http::response([
            'success' => true,
            'data' => ['code' => 'MCP1'],
            'meta' => ['idempotency' => ['replayed' => false]],
        ], 201),
    ]);

    XChangePartnerServer::tool(IssuePayCodeTool::class, [
        'pay_code' => mcpPayCode(),
        'confirm_issue' => true,
        'idempotency_key' => 'mcp-issue-001',
        'correlation_id' => 'mcp-run-001',
    ])->assertOk()->assertSee('MCP1');

    Http::assertSent(fn (Request $request): bool => $request->hasHeader('Idempotency-Key', 'mcp-issue-001')
        && $request->hasHeader('X-Correlation-ID', 'mcp-run-001'));
});

it('requires explicit confirmation before issuing', function () {
    authenticateMcpContext(['pay-codes:issue']);

    XChangePartnerServer::tool(IssuePayCodeTool::class, [
        'pay_code' => mcpPayCode(),
        'confirm_issue' => false,
        'idempotency_key' => 'mcp-issue-002',
    ])->assertHasErrors();

    Http::assertNothingSent();
});

it('reads only the sanitized Pay Code representation from the Partner API', function () {
    authenticateMcpContext(['pay-codes:read']);
    Http::fake([
        'https://partner.example.test/api/partner/v1/pay-codes/MCP1' => Http::response([
            'success' => true,
            'data' => [
                'code' => 'MCP1',
                'status' => 'active',
                'amount_minor' => 5000,
                'currency' => 'PHP',
            ],
        ]),
    ]);

    XChangePartnerServer::tool(GetPayCodeTool::class, ['code' => 'MCP1'])
        ->assertOk()
        ->assertSee('amount_minor')
        ->assertSee('5000');

    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
        && $request->url() === 'https://partner.example.test/api/partner/v1/pay-codes/MCP1'
        && $request->hasHeader('Authorization', 'Bearer partner-token'));
});

it('cancels through the idempotent Partner API endpoint only after confirmation', function () {
    authenticateMcpContext(['pay-codes:cancel']);
    Http::fake([
        'https://partner.example.test/api/partner/v1/pay-codes/MCP1/cancellation' => Http::response([
            'success' => true,
            'data' => ['code' => 'MCP1', 'status' => 'cancelled'],
        ]),
    ]);

    XChangePartnerServer::tool(CancelPayCodeTool::class, [
        'code' => 'MCP1',
        'reason' => 'Recipient request withdrawn.',
        'confirm_cancel' => true,
        'idempotency_key' => 'mcp-cancel-001',
    ])->assertOk()->assertSee('cancelled');

    Http::assertSent(fn (Request $request): bool => $request->data() === [
        'reason' => 'Recipient request withdrawn.',
    ] && $request->hasHeader('Idempotency-Key', 'mcp-cancel-001'));
});

it('creates a funded order through the client-bound Partner API', function () {
    authenticateMcpContext(['funded-orders:create']);
    Http::fake([
        'https://partner.example.test/api/partner/v1/funded-orders' => Http::response([
            'success' => true,
            'data' => ['reference' => '01M4P000000000000000000000', 'status' => 'awaiting_payment'],
        ], 201),
    ]);

    XChangePartnerServer::tool(CreateFundedOrderTool::class, [
        'pay_code' => mcpPayCode(),
        'funding_method' => 'bank_transfer',
        'payer_mobile' => '09175180722',
        'confirm_order' => true,
        'idempotency_key' => 'funded-001',
    ])->assertOk()->assertSee('awaiting_payment');

    Http::assertSent(fn (Request $request): bool => $request->hasHeader('Idempotency-Key', 'funded-001')
        && data_get($request->data(), 'funding_method') === 'bank_transfer'
        && data_get($request->data(), 'payer_mobile') === '09175180722');
});

it('does not create a funded order without confirmation', function () {
    authenticateMcpContext(['funded-orders:create']);

    XChangePartnerServer::tool(CreateFundedOrderTool::class, [
        'pay_code' => mcpPayCode(),
        'funding_method' => 'qr_ph',
        'confirm_order' => false,
        'idempotency_key' => 'funded-002',
    ])->assertHasErrors();

    Http::assertNothingSent();
});

it('polls only through the authenticated funded-order endpoint', function () {
    authenticateMcpContext(['funded-orders:read']);
    $reference = '01M4P000000000000000000000';
    Http::fake([
        'https://partner.example.test/api/partner/v1/funded-orders/'.$reference => Http::response([
            'success' => true,
            'data' => ['reference' => $reference, 'status' => 'issued', 'pay_code' => ['claim_url' => 'https://example.test/x/claim/TEST']],
        ]),
    ]);

    XChangePartnerServer::tool(GetFundedOrderTool::class, ['reference' => $reference])
        ->assertOk()->assertSee('claim_url');

    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
        && $request->hasHeader('Authorization', 'Bearer partner-token'));
});
