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

#[Name('x-change-partner-operating-guide')]
#[Description('Safety and operating rules for the governed X-Change Partner MCP.')]
#[Uri('x-change://partner-api/operating-guide')]
#[MimeType('text/markdown')]
class OperatingGuideResource extends Resource
{
    public function handle(Request $request): Response
    {
        return Response::text(<<<'MARKDOWN'
            # X-Change Partner MCP

            This server is an HTTP adapter over the governed X-Change Partner API.

            - Inspect capabilities before estimating or issuing.
            - Estimate before issuing whenever the amount or instructions changed.
            - Reuse an idempotency key only for the same logical mutation and identical payload.
            - Never infer or supply an issuer identity; OAuth binds the issuer Account.
            - Cancellation is terminal for an eligible Pay Code and does not refund commercial charges.
            - Never use X-Change's legacy lifecycle API surface.
            MARKDOWN);
    }
}
