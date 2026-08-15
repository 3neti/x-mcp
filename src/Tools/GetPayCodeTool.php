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

#[Name('get_pay_code')]
#[Title('Get Pay Code')]
#[Description('Inspect the sanitized lifecycle status of a Pay Code owned by the OAuth client-bound Account.')]
#[IsReadOnly]
#[IsIdempotent]
#[IsOpenWorld]
class GetPayCodeTool extends Tool
{
    use InteractsWithPartnerApi;

    public function __construct(
        protected PartnerApiTransportContract $partnerApi,
        protected PartnerBearerTokenContext $context,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return ['code' => $schema->string()->description('Pay Code to inspect.')->required()];
    }

    public function shouldRegister(Request $request): bool
    {
        return $this->context->allows('pay-codes:read');
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate(['code' => ['required', 'string', 'max:120']]);

        return $this->partnerResponse($this->partnerApi->send(
            'GET',
            'pay-codes/'.rawurlencode($validated['code']),
            $this->context->token(),
        ));
    }
}
