<?php

declare(strict_types=1);

namespace LBHurtado\XMcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Name('x-change-partner-contract')]
#[Description('Sanitized operation, scope, and money conventions for the X-Change Partner API.')]
#[Uri('x-change://partner-api/contract')]
#[MimeType('application/json')]
class PartnerContractResource extends Resource
{
    public function handle(Request $request): Response
    {
        return Response::json([
            'version' => (string) config('x-mcp.expected_partner_contract_version'),
            'money' => [
                'instructions' => 'major_units',
                'status_resources' => 'integer_minor_units',
                'currency' => 'ISO_4217',
            ],
            'operations' => [
                'inspect_capabilities' => ['scope' => 'capabilities:read', 'mutation' => false],
                'estimate_pay_code' => ['scope' => 'pay-codes:estimate', 'mutation' => false],
                'issue_pay_code' => ['scope' => 'pay-codes:issue', 'mutation' => true, 'idempotency_required' => true],
                'get_pay_code' => ['scope' => 'pay-codes:read', 'mutation' => false],
                'cancel_pay_code' => ['scope' => 'pay-codes:cancel', 'mutation' => true, 'idempotency_required' => true],
            ],
        ]);
    }
}
