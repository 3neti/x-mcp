<?php

declare(strict_types=1);

namespace LBHurtado\XMcp\Tools\Concerns;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use LBHurtado\XMcp\Data\PublicIssuanceApiResponseData;

trait InteractsWithPublicIssuanceApi
{
    /** @return array<string, mixed> */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'ok' => $schema->boolean()->required(),
            'http_status' => $schema->integer()->required(),
            'data' => $schema->object()->nullable(),
            'error' => $schema->object()->nullable(),
        ];
    }

    protected function publicIssuanceResponse(PublicIssuanceApiResponseData $response): Response|ResponseFactory
    {
        $structured = $response->structured();

        if ($response->successful()) {
            return Response::structured($structured);
        }

        return Response::make(Response::error((string) data_get(
            $structured,
            'error.message',
            'The public issuance service rejected the request.',
        )))->withStructuredContent($structured);
    }
}
