<?php

declare(strict_types=1);

namespace LBHurtado\XMcp\Services;

use LogicException;

class PartnerBearerTokenContext
{
    protected ?string $token = null;

    /** @var array<string, mixed> */
    protected array $capabilities = [];

    /** @param array<string, mixed> $capabilities */
    public function set(string $token, array $capabilities): void
    {
        $this->token = $token;
        $this->capabilities = $capabilities;
    }

    public function token(): string
    {
        return $this->token ?? throw new LogicException('The Partner MCP request is not authenticated.');
    }

    public function allows(string $scope): bool
    {
        return in_array($scope, (array) data_get($this->capabilities, 'data.operations', []), true);
    }

    /** @return array<string, mixed> */
    public function capabilities(): array
    {
        return $this->capabilities;
    }
}
