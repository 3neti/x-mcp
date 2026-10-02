<?php

declare(strict_types=1);

namespace LBHurtado\XMcp\Tools\Concerns;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;

trait DefinesPublicIssuanceSchema
{
    /** @return array<string, mixed> */
    protected function publicIssuanceSchema(JsonSchema $schema): array
    {
        return [
            'amount_minor' => $schema->integer()->description('Pay Code principal in minor currency units.')->required(),
            'currency' => $schema->string()->description('ISO currency code. Public issuance currently supports PHP.')->required(),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{amount_minor: int, currency: string}
     */
    protected function validatedPublicIssuance(array $input): array
    {
        $amount = filter_var($input['amount_minor'] ?? null, FILTER_VALIDATE_INT);
        $currency = strtoupper(trim((string) ($input['currency'] ?? '')));
        $errors = [];

        if ($amount === false || $amount < 1) {
            $errors['amount_minor'][] = 'Provide a positive amount in minor currency units.';
        }

        if ($currency !== 'PHP') {
            $errors['currency'][] = 'Public issuance currently supports PHP.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return ['amount_minor' => $amount, 'currency' => $currency];
    }
}
