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
use LBHurtado\XMcp\Contracts\PartnerApiTransportContract;
use LBHurtado\XMcp\Services\PartnerBearerTokenContext;
use LBHurtado\XMcp\Tools\Concerns\InteractsWithPartnerApi;

#[Name('cancel_pay_code')]
#[Title('Cancel Pay Code')]
#[Description('Cancel an eligible unclaimed Pay Code owned by the OAuth client-bound Account. Commercial charges remain retained.')]
#[IsDestructive]
#[IsIdempotent]
#[IsOpenWorld]
class CancelPayCodeTool extends Tool
{
    use InteractsWithPartnerApi;

    public function __construct(
        protected PartnerApiTransportContract $partnerApi,
        protected PartnerBearerTokenContext $context,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'code' => $schema->string()->required(),
            'reason' => $schema->string()->description('Audit reason for cancellation.')->required(),
            'confirm_cancel' => $schema->boolean()->description('Must be true after confirming this Pay Code should become terminal.')->required(),
            'idempotency_key' => $schema->string()->description('Stable key reused only for this cancellation.')->required(),
            'correlation_id' => $schema->string()->nullable(),
        ];
    }

    public function shouldRegister(Request $request): bool
    {
        return $this->context->allows('pay-codes:cancel');
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:120'],
            'reason' => ['required', 'string', 'max:255'],
            'confirm_cancel' => ['required', 'accepted'],
            'idempotency_key' => ['required', 'string', 'max:160'],
            'correlation_id' => ['nullable', 'string', 'max:160'],
        ], [
            'confirm_cancel.accepted' => 'Explicitly confirm cancellation after reviewing the Pay Code status.',
        ]);
        $headers = ['Idempotency-Key' => $validated['idempotency_key']];

        if (filled($validated['correlation_id'] ?? null)) {
            $headers['X-Correlation-ID'] = $validated['correlation_id'];
        }

        return $this->partnerResponse($this->partnerApi->send(
            'POST',
            'pay-codes/'.rawurlencode($validated['code']).'/cancellation',
            $this->context->token(),
            ['reason' => $validated['reason']],
            $headers,
        ));
    }
}
