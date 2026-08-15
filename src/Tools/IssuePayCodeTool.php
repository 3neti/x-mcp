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

#[Name('issue_pay_code')]
#[Title('Issue Pay Code')]
#[Description('Issue a Pay Code from the OAuth client-bound Account. Requires explicit confirmation and a stable idempotency key.')]
#[IsIdempotent]
#[IsOpenWorld]
class IssuePayCodeTool extends Tool
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
            'confirm_issue' => $schema->boolean()->description('Must be true after the caller reviews the amount and estimated cost.')->required(),
            'idempotency_key' => $schema->string()->description('Stable key reused only for the same logical issuance.')->required(),
            'correlation_id' => $schema->string()->nullable(),
        ];
    }

    public function shouldRegister(Request $request): bool
    {
        return $this->context->allows('pay-codes:issue');
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'confirm_issue' => ['required', 'accepted'],
            'idempotency_key' => ['required', 'string', 'max:160'],
            'correlation_id' => ['nullable', 'string', 'max:160'],
        ], [
            'confirm_issue.accepted' => 'Review the amount and estimated cost, then explicitly confirm issuance.',
        ]);
        $payload = $this->validatedPayCode($request->all());
        $headers = ['Idempotency-Key' => $validated['idempotency_key']];

        if (filled($validated['correlation_id'] ?? null)) {
            $headers['X-Correlation-ID'] = $validated['correlation_id'];
        }

        return $this->partnerResponse($this->partnerApi->send(
            'POST',
            'pay-codes',
            $this->context->token(),
            $payload,
            $headers,
        ));
    }
}
