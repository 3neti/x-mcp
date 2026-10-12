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
use LBHurtado\XMcp\Contracts\PartnerApiTransportContract;
use LBHurtado\XMcp\Services\PartnerBearerTokenContext;
use LBHurtado\XMcp\Tools\Concerns\DefinesPayCodeSchema;
use LBHurtado\XMcp\Tools\Concerns\InteractsWithPartnerApi;

#[Name('create_funded_order')]
#[Title('Create Funded Pay Code Order')]
#[Description('Create one customer-funded Pay Code checkout and return QR Ph or bank transfer instructions. The payer must pay the exact amount once; issuance follows verified payment.')]
#[IsIdempotent]
#[IsOpenWorld]
final class CreateFundedOrderTool extends Tool
{
    use DefinesPayCodeSchema;
    use InteractsWithPartnerApi;

    public function __construct(
        protected PartnerApiTransportContract $partnerApi,
        protected PartnerBearerTokenContext $context,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'pay_code' => $this->payCodeSchema($schema),
            'funding_method' => $schema->string()->enum(['qr_ph', 'bank_transfer'])->required(),
            'payer_mobile' => $schema->string()->description('Required before bank transfer instructions are revealed.')->nullable(),
            'confirm_order' => $schema->boolean()->description('Must be true after the caller reviews the amount and payment method.')->required(),
            'idempotency_key' => $schema->string()->description('Stable key reused only for the same checkout.')->required(),
        ];
    }

    public function shouldRegister(Request $request): bool
    {
        return $this->context->allows('funded-orders:create');
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'confirm_order' => ['required', 'accepted'],
            'funding_method' => ['required', 'in:qr_ph,bank_transfer'],
            'payer_mobile' => ['required_if:funding_method,bank_transfer', 'nullable', 'string', 'max:32'],
            'idempotency_key' => ['required', 'string', 'max:160'],
        ]);
        $payload = $this->validatedPayCode($request->all());
        $payload['funding_method'] = $validated['funding_method'];
        $payload['payer_mobile'] = $validated['payer_mobile'] ?? null;

        return $this->partnerResponse($this->partnerApi->send(
            'POST',
            'funded-orders',
            $this->context->token(),
            $payload,
            ['Idempotency-Key' => $validated['idempotency_key']],
        ));
    }
}
