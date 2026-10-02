<?php

declare(strict_types=1);

namespace LBHurtado\XMcp\Data;

final readonly class PublicIssuanceApiResponseData
{
    /** @param array<string, mixed> $body */
    public function __construct(
        public int $status,
        public array $body,
        public ?int $retryAfterSeconds = null,
    ) {}

    public function successful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    /** @return array<string, mixed> */
    public function structured(): array
    {
        return [
            'ok' => $this->successful(),
            'http_status' => $this->status,
            'data' => $this->successful() ? $this->body : null,
            'error' => $this->successful() ? null : [
                'code' => data_get($this->body, 'code', 'public_issuance_api_error'),
                'message' => data_get($this->body, 'message', 'The public issuance service rejected the request.'),
                'fields' => data_get($this->body, 'errors'),
                'retry_after_seconds' => $this->retryAfterSeconds,
            ],
        ];
    }
}
