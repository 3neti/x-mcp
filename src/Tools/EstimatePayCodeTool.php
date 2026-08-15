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
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use LBHurtado\XMcp\Contracts\PartnerApiTransportContract;
use LBHurtado\XMcp\Services\PartnerBearerTokenContext;
use LBHurtado\XMcp\Tools\Concerns\DefinesPayCodeSchema;
use LBHurtado\XMcp\Tools\Concerns\InteractsWithPartnerApi;

#[Name('estimate_pay_code')]
#[Title('Estimate Pay Code')]
#[Description('Obtain the authoritative issue cost without reserving funds or issuing a Pay Code.')]
#[IsReadOnly]
#[IsIdempotent]
#[IsOpenWorld]
class EstimatePayCodeTool extends Tool
{
    use DefinesPayCodeSchema;
    use InteractsWithPartnerApi;

    public function __construct(
        protected PartnerApiTransportContract $partnerApi,
        protected PartnerBearerTokenContext $context,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return ['pay_code' => $this->payCodeSchema($schema)];
    }

    public function shouldRegister(Request $request): bool
    {
        return $this->context->allows('pay-codes:estimate');
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $payload = $this->validatedPayCode($request->all());

        return $this->partnerResponse($this->partnerApi->send(
            'POST',
            'pay-code-estimates',
            $this->context->token(),
            $payload,
        ));
    }
}
