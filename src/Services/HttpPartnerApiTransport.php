<?php

declare(strict_types=1);

namespace LBHurtado\XMcp\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use LBHurtado\XMcp\Contracts\PartnerApiTransportContract;
use LBHurtado\XMcp\Data\PartnerApiResponseData;
use Throwable;

class HttpPartnerApiTransport implements PartnerApiTransportContract
{
    public function __construct(protected Factory $http) {}

    public function send(
        string $method,
        string $path,
        string $bearerToken,
        array $payload = [],
        array $headers = [],
    ): PartnerApiResponseData {
        $baseUrl = rtrim((string) config('x-mcp.api_base_url'), '/');

        if ($baseUrl === '') {
            return $this->unavailable('The Partner API base URL is not configured.');
        }

        $method = strtoupper($method);
        $request = $this->request($bearerToken, $headers);

        if ($this->mayRetry($method, $path)) {
            $request = $request->retry(
                (array) config('x-mcp.read_retry_delays_ms', [100, 250]),
                fn (Throwable $exception): bool => $exception instanceof ConnectionException,
                throw: false,
            );
        }

        try {
            $response = $method === 'GET'
                ? $request->get($baseUrl.'/'.ltrim($path, '/'), $payload)
                : $request->send($method, $baseUrl.'/'.ltrim($path, '/'), ['json' => $payload]);
        } catch (ConnectionException) {
            return $this->unavailable('The Partner API is temporarily unavailable.');
        }

        return $this->map($response);
    }

    /** @param array<string, string> $headers */
    protected function request(string $bearerToken, array $headers): PendingRequest
    {
        return $this->http
            ->acceptJson()
            ->asJson()
            ->withToken($bearerToken)
            ->withHeaders($headers)
            ->connectTimeout((int) config('x-mcp.connect_timeout_seconds', 5))
            ->timeout((int) config('x-mcp.request_timeout_seconds', 30));
    }

    protected function mayRetry(string $method, string $path): bool
    {
        return $method === 'GET' || ($method === 'POST' && trim($path, '/') === 'pay-code-estimates');
    }

    protected function map(Response $response): PartnerApiResponseData
    {
        $body = $response->json();

        if (! is_array($body)) {
            return new PartnerApiResponseData(502, [
                'success' => false,
                'code' => 'malformed_partner_response',
                'message' => 'The Partner API returned an invalid response.',
            ]);
        }

        $retryAfter = $response->header('Retry-After');

        return new PartnerApiResponseData(
            status: $response->status(),
            body: $body,
            retryAfterSeconds: is_numeric($retryAfter) ? (int) $retryAfter : null,
        );
    }

    protected function unavailable(string $message): PartnerApiResponseData
    {
        return new PartnerApiResponseData(503, [
            'success' => false,
            'code' => 'partner_api_unavailable',
            'message' => $message,
        ]);
    }
}
