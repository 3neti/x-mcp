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
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use LBHurtado\XMcp\Contracts\PublicIssuanceApiTransportContract;
use LBHurtado\XMcp\Tools\Concerns\InteractsWithPublicIssuanceApi;

#[Name('discover_on_demand_issuance')]
#[Title('Discover On-Demand Pay Code Issuance')]
#[Description('Inspect availability, supported amounts, funding methods, pricing links, and the browser handoff for public Pay Code issuance.')]
#[IsReadOnly(true)]
#[IsDestructive(false)]
#[IsIdempotent(true)]
#[IsOpenWorld(false)]
final class DiscoverOnDemandIssuanceTool extends Tool
{
    use InteractsWithPublicIssuanceApi;

    public function __construct(private readonly PublicIssuanceApiTransportContract $api) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        return $this->publicIssuanceResponse($this->api->send('GET', ''));
    }
}
