<?php

declare(strict_types=1);

namespace LBHurtado\XMcp\Tools\Concerns;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\ObjectType;

trait DefinesPayCodeSchema
{
    protected function payCodeSchema(JsonSchema $schema): ObjectType
    {
        return $schema->object([
            'cash' => $schema->object([
                'amount' => $schema->number()->description('Pay Code value in major currency units.')->required(),
                'currency' => $schema->string()->description('ISO 4217 currency code.')->default('PHP')->required(),
                'settlement_rail' => $schema->string()->enum(['INSTAPAY', 'PESONET'])->nullable(),
                'validation' => $schema->object([
                    'mobile' => $schema->string()->nullable(),
                    'payable' => $schema->string()->nullable(),
                    'secret' => $schema->string()->nullable(),
                ])->nullable()->withoutAdditionalProperties(),
            ])->required()->withoutAdditionalProperties(),
            'inputs' => $schema->object([
                'fields' => $schema->array()->items($schema->string())->default([])->required(),
            ])->required()->withoutAdditionalProperties(),
            'feedback' => $schema->object([
                'email' => $schema->string()->nullable(),
                'mobile' => $schema->string()->nullable(),
                'webhook' => $schema->string()->nullable(),
            ])->required()->withoutAdditionalProperties(),
            'rider' => $schema->object([
                'message' => $schema->string()->nullable(),
                'url' => $schema->string()->nullable(),
                'splash' => $schema->string()->nullable(),
            ])->required()->withoutAdditionalProperties(),
            'count' => $schema->integer()->default(1),
            'ttl' => $schema->union(['integer', 'string', 'null']),
        ])->required()->withoutAdditionalProperties();
    }

    /** @return array<string, mixed> */
    protected function validatedPayCode(array $arguments): array
    {
        return validator($arguments, [
            'pay_code' => ['required', 'array'],
            'pay_code.cash' => ['required', 'array'],
            'pay_code.cash.amount' => ['required', 'numeric', 'gt:0'],
            'pay_code.cash.currency' => ['required', 'string', 'size:3'],
            'pay_code.cash.settlement_rail' => ['nullable', 'in:INSTAPAY,PESONET'],
            'pay_code.cash.validation' => ['nullable', 'array'],
            'pay_code.cash.validation.mobile' => ['nullable', 'string', 'max:40'],
            'pay_code.cash.validation.payable' => ['nullable', 'string', 'max:120'],
            'pay_code.cash.validation.secret' => ['nullable', 'string', 'max:255'],
            'pay_code.inputs' => ['required', 'array'],
            'pay_code.inputs.fields' => ['required', 'array'],
            'pay_code.inputs.fields.*' => ['string', 'max:80'],
            'pay_code.feedback' => ['required', 'array'],
            'pay_code.feedback.email' => ['nullable', 'email'],
            'pay_code.feedback.mobile' => ['nullable', 'string', 'max:40'],
            'pay_code.feedback.webhook' => ['nullable', 'url:http,https'],
            'pay_code.rider' => ['required', 'array'],
            'pay_code.rider.message' => ['nullable', 'string', 'max:2000'],
            'pay_code.rider.url' => ['nullable', 'url:http,https', 'max:2048'],
            'pay_code.rider.splash' => ['nullable', 'string', 'max:51200'],
            'pay_code.count' => ['nullable', 'integer', 'min:1'],
            'pay_code.ttl' => ['nullable'],
        ])->validate()['pay_code'];
    }
}
