<?php

declare(strict_types=1);

namespace LBHurtado\XMcp\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use LBHurtado\XMcp\Contracts\PartnerApiTransportContract;
use LBHurtado\XMcp\Services\PartnerBearerTokenContext;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatePartnerBearer
{
    public function __construct(
        protected PartnerApiTransportContract $partnerApi,
        protected PartnerBearerTokenContext $context,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! is_string($token) || trim($token) === '') {
            throw new AuthenticationException('A Partner API bearer token is required.');
        }

        $capabilities = $this->partnerApi->send('GET', 'capabilities', $token);

        if (! $capabilities->successful()) {
            throw new AuthenticationException('The Partner API bearer token is invalid or inactive.');
        }

        $this->assertCompatibleContract($capabilities->body);

        $this->context->set($token, $capabilities->body);

        return $next($request);
    }

    /** @param array<string, mixed> $capabilities */
    protected function assertCompatibleContract(array $capabilities): void
    {
        $expectedVersion = trim((string) config('x-mcp.expected_partner_contract_version'));
        $actualVersion = (string) data_get($capabilities, 'data.contract.version');

        if ($expectedVersion !== '' && $actualVersion !== $expectedVersion) {
            throw new LogicException('The configured Partner API contract version is not compatible with X-MCP.');
        }

        $expectedHash = trim((string) config('x-mcp.expected_partner_contract_sha256'));
        $actualHash = (string) data_get($capabilities, 'data.contract.sha256');

        if ($expectedHash !== '' && ! hash_equals($expectedHash, $actualHash)) {
            throw new LogicException('The configured Partner API contract hash does not match X-MCP.');
        }
    }
}
