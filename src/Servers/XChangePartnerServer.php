<?php

declare(strict_types=1);

namespace LBHurtado\XMcp\Servers;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use LBHurtado\XMcp\Resources\OperatingGuideResource;
use LBHurtado\XMcp\Resources\PartnerContractResource;
use LBHurtado\XMcp\Resources\SafetyModelResource;
use LBHurtado\XMcp\Tools\CancelPayCodeTool;
use LBHurtado\XMcp\Tools\EstimatePayCodeTool;
use LBHurtado\XMcp\Tools\GetPayCodeTool;
use LBHurtado\XMcp\Tools\InspectCapabilitiesTool;
use LBHurtado\XMcp\Tools\IssuePayCodeTool;

#[Name('X-Change Partner MCP')]
#[Version('0.1.0')]
#[Instructions('Use these tools only for the OAuth client-bound Account. Inspect capabilities and estimate before issuing. Financial mutations require explicit confirmation and stable idempotency keys.')]
class XChangePartnerServer extends Server
{
    protected array $tools = [
        InspectCapabilitiesTool::class,
        EstimatePayCodeTool::class,
        IssuePayCodeTool::class,
        GetPayCodeTool::class,
        CancelPayCodeTool::class,
    ];

    protected array $resources = [
        OperatingGuideResource::class,
        PartnerContractResource::class,
        SafetyModelResource::class,
    ];
}
