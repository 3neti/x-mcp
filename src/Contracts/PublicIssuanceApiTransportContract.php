<?php

declare(strict_types=1);

namespace LBHurtado\XMcp\Contracts;

use LBHurtado\XMcp\Data\PublicIssuanceApiResponseData;

interface PublicIssuanceApiTransportContract
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(string $method, string $path, array $payload = []): PublicIssuanceApiResponseData;
}
