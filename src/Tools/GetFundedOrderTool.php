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
use LBHurtado\XMcp\Tools\Concerns\InteractsWithPartnerApi;

#[Name('get_funded_order')]
#[Title('Get Funded Pay Code Order')]
#[Description('Poll a checkout created by this approved client. A Pay Code claim URL appears after verified payment and issuance.')]
#[IsReadOnly]
#[IsIdempotent]
#[IsOpenWorld]
final class GetFundedOrderTool extends Tool
{
    use InteractsWithPartnerApi;

    public function __construct(
        protected PartnerApiTransportContract $partnerApi,
        protected PartnerBearerTokenContext $context,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return ['reference' => $schema->string()->description('Funded-order reference returned at creation.')->required()];
    }

    public function shouldRegister(Request $request): bool
    {
        return $this->context->allows('funded-orders:read');
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'regex:/^[0-9A-HJKMNP-TV-Z]{26}$/'],
        ]);

        return $this->partnerResponse($this->partnerApi->send(
            'GET',
            'funded-orders/'.$validated['reference'],
            $this->context->token(),
        ));
    }
}
