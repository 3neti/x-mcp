<?php

declare(strict_types=1);

namespace LBHurtado\XMcp\Tests;

use Laravel\Mcp\Server\McpServiceProvider;
use LBHurtado\XMcp\XMcpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            McpServiceProvider::class,
            XMcpServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('x-mcp.enabled', true);
        $app['config']->set('x-mcp.public_discovery_enabled', true);
        $app['config']->set('x-mcp.api_base_url', 'https://partner.example.test/api/partner/v1');
        $app['config']->set('x-mcp.public_issuance.enabled', true);
        $app['config']->set('x-mcp.public_issuance.discovery_enabled', true);
        $app['config']->set('x-mcp.public_issuance.api_base_url', 'https://public.example.test/api/x/v1/public-issuance');
        $app['config']->set('x-mcp.connect_timeout_seconds', 5);
        $app['config']->set('x-mcp.request_timeout_seconds', 30);
        $app['config']->set('x-mcp.read_retry_delays_ms', []);
        $app['config']->set('x-mcp.expected_partner_contract_version', '1.0.0');
    }
}
