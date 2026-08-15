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

#[Name('x-change-partner-safety-model')]
#[Description('Authority and privacy boundary applied to every X-Change Partner MCP tool.')]
#[Uri('x-change://partner-api/safety-model')]
#[MimeType('text/markdown')]
class SafetyModelResource extends Resource
{
    public function handle(Request $request): Response
    {
        return Response::text(<<<'MARKDOWN'
            # Safety Model

            The bearer token binds exactly one approved Partner client to one issuer Account. X-MCP cannot select another issuer.

            The Partner API remains authoritative for scopes, mandates, pricing, Account funds, Treasury capacity, ownership, idempotency, and terminal release. Tool annotations and confirmation fields improve AI behavior but never replace those controls.

            Tool output excludes raw voucher instructions, claim evidence, settlement envelopes, issuer identifiers, OAuth credentials, provider payloads, and Treasury internals.
            MARKDOWN);
    }
}
