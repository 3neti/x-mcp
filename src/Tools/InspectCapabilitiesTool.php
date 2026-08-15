<?php

declare(strict_types=1);

namespace LBHurtado\XMcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use LBHurtado\XMcp\Contracts\PartnerApiTransportContract;
use LBHurtado\XMcp\Services\PartnerBearerTokenContext;
use LBHurtado\XMcp\Tools\Concerns\InteractsWithPartnerApi;

#[Name('inspect_capabilities')]
#[Title('Inspect X-Change Capabilities')]
#[Description('Inspect the authenticated Partner API scopes, currencies, rails, profiles, and financial limits.')]
#[IsReadOnly]
#[IsIdempotent]
#[IsOpenWorld]
class InspectCapabilitiesTool extends Tool
{
    use InteractsWithPartnerApi;

    public function __construct(
        protected PartnerApiTransportContract $partnerApi,
        protected PartnerBearerTokenContext $context,
    ) {}

    public function shouldRegister(Request $request): bool
    {
        return $this->context->allows('capabilities:read');
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        return $this->partnerResponse($this->partnerApi->send('GET', 'capabilities', $this->context->token()));
    }
}
