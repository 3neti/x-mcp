<?php

declare(strict_types=1);

namespace LBHurtado\XMcp\Tools\Concerns;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use LBHurtado\XMcp\Data\PartnerApiResponseData;

trait InteractsWithPartnerApi
{
    /** @return array<string, mixed> */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'ok' => $schema->boolean()->required(),
            'http_status' => $schema->integer()->required(),
            'data' => $schema->object()->nullable(),
            'meta' => $schema->object()->nullable(),
            'error' => $schema->object()->nullable(),
        ];
    }

    protected function partnerResponse(PartnerApiResponseData $response): Response|ResponseFactory
    {
        $structured = $response->structured();

        if ($response->successful()) {
            return Response::structured($structured);
        }

        return Response::make(Response::error((string) data_get(
            $structured,
            'error.message',
            'The Partner API rejected the request.',
        )))->withStructuredContent($structured);
    }
}
