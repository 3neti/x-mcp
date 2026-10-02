<?php

declare(strict_types=1);

namespace LBHurtado\XMcp\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;
use LBHurtado\XMcp\Contracts\PublicIssuanceApiTransportContract;
use LBHurtado\XMcp\Data\PublicIssuanceApiResponseData;

final class HttpPublicIssuanceApiTransport implements PublicIssuanceApiTransportContract
{
    public function __construct(private readonly Factory $http) {}

    public function send(string $method, string $path, array $payload = []): PublicIssuanceApiResponseData
    {
        $baseUrl = rtrim((string) config('x-mcp.public_issuance.api_base_url'), '/');

        if ($baseUrl === '') {
            return $this->unavailable('The public issuance API base URL is not configured.');
        }

        try {
            $url = $baseUrl.($path === '' ? '' : '/'.ltrim($path, '/'));
            $request = $this->http
                ->acceptJson()
                ->asJson()
                ->connectTimeout((int) config('x-mcp.connect_timeout_seconds', 5))
                ->timeout((int) config('x-mcp.request_timeout_seconds', 30))
                ->retry(
                    (array) config('x-mcp.read_retry_delays_ms', [100, 250]),
                    fn (\Throwable $exception): bool => $exception instanceof ConnectionException,
                    throw: false,
                );
            $response = strtoupper($method) === 'GET'
                ? $request->get($url, $payload)
                : $request->send(strtoupper($method), $url, ['json' => $payload]);
        } catch (ConnectionException) {
            return $this->unavailable('The public issuance API is temporarily unavailable.');
        }

        return $this->map($response);
    }

    private function map(Response $response): PublicIssuanceApiResponseData
    {
        $body = $response->json();

        if (! is_array($body)) {
            return $this->unavailable('The public issuance API returned an invalid response.');
        }

        $retryAfter = $response->header('Retry-After');

        return new PublicIssuanceApiResponseData(
            status: $response->status(),
            body: $body,
            retryAfterSeconds: is_numeric($retryAfter) ? (int) $retryAfter : null,
        );
    }

    private function unavailable(string $message): PublicIssuanceApiResponseData
    {
        return new PublicIssuanceApiResponseData(503, [
            'code' => 'public_issuance_api_unavailable',
            'message' => $message,
        ]);
    }
}
