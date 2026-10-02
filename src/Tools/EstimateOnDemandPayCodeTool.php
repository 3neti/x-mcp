<?php

declare(strict_types=1);

namespace LBHurtado\XMcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
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
use LBHurtado\XMcp\Tools\Concerns\DefinesPublicIssuanceSchema;
use LBHurtado\XMcp\Tools\Concerns\InteractsWithPublicIssuanceApi;

#[Name('estimate_on_demand_pay_code')]
#[Title('Estimate an On-Demand Pay Code')]
#[Description('Obtain the authoritative total required for a basic public Pay Code without creating an order, reserving funds, or issuing a Pay Code.')]
#[IsReadOnly(true)]
#[IsDestructive(false)]
#[IsIdempotent(true)]
#[IsOpenWorld(false)]
final class EstimateOnDemandPayCodeTool extends Tool
{
    use DefinesPublicIssuanceSchema;
    use InteractsWithPublicIssuanceApi;

    public function __construct(private readonly PublicIssuanceApiTransportContract $api) {}

    public function schema(JsonSchema $schema): array
    {
        return $this->publicIssuanceSchema($schema);
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        return $this->publicIssuanceResponse($this->api->send(
            'POST',
            'estimate',
            $this->validatedPublicIssuance($request->all()),
        ));
    }
}
