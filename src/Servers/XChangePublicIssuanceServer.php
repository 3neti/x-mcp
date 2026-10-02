<?php

declare(strict_types=1);

namespace LBHurtado\XMcp\Servers;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use LBHurtado\XMcp\Tools\DiscoverOnDemandIssuanceTool;
use LBHurtado\XMcp\Tools\EstimateOnDemandPayCodeTool;
use LBHurtado\XMcp\Tools\PrepareOnDemandHandoffTool;

#[Name('X-Change Public Issuance MCP')]
#[Version('0.2.0')]
#[Instructions('These tools are anonymous and read-only. They may discover, estimate, or prepare a browser handoff. They never create an order, request payment, reserve funds, or issue a Pay Code.')]
final class XChangePublicIssuanceServer extends Server
{
    protected array $tools = [
        DiscoverOnDemandIssuanceTool::class,
        EstimateOnDemandPayCodeTool::class,
        PrepareOnDemandHandoffTool::class,
    ];
}
