<?php

declare(strict_types=1);

namespace LBHurtado\XMcp\Contracts;

use LBHurtado\XMcp\Data\PartnerApiResponseData;

interface PartnerApiTransportContract
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     */
    public function send(
        string $method,
        string $path,
        string $bearerToken,
        array $payload = [],
        array $headers = [],
    ): PartnerApiResponseData;
}
