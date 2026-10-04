<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

it('fails closed when the MCP endpoint is not commissioned', function () {
    config()->set('x-mcp.enabled', false);
    config()->set('x-mcp.api_base_url', null);

    expect(Artisan::call('x-mcp:doctor', ['--json' => true]))->toBe(1);

    $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

    expect($report)
        ->toHaveKey('schema', 'x-mcp.doctor.v1')
        ->toHaveKey('ready', false);
});

it('reports a ready sanitized local configuration', function () {
    config()->set('x-mcp.enabled', true);
    config()->set('x-mcp.api_base_url', 'https://partner.example.test/api/partner/v1');
    config()->set('x-mcp.expected_partner_contract_version', '1.4.0');
    config()->set('x-mcp.endpoint', '/mcp/x-change');

    expect(Artisan::call('x-mcp:doctor', ['--json' => true]))->toBe(0);

    $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

    expect($report)
        ->toHaveKey('ready', true)
        ->and(collect($report['checks'])->pluck('key')->all())
        ->toContain('partner_api');
});
