<?php

declare(strict_types=1);

namespace LBHurtado\XMcp\Data;

final readonly class PartnerApiResponseData
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
            'data' => $this->successful() ? data_get($this->body, 'data') : null,
            'meta' => data_get($this->body, 'meta'),
            'error' => $this->successful() ? null : [
                'code' => data_get($this->body, 'code', 'partner_api_error'),
                'message' => data_get($this->body, 'message', 'The Partner API rejected the request.'),
                'fields' => data_get($this->body, 'errors'),
                'retry_after_seconds' => $this->retryAfterSeconds,
            ],
        ];
    }
}
