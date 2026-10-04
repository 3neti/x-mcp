<?php

declare(strict_types=1);

return [
    'enabled' => env('XMCP_ENABLED', false),
    'public_discovery_enabled' => env('XMCP_PUBLIC_DISCOVERY_ENABLED', true),
    'endpoint' => env('XMCP_ENDPOINT', '/mcp/x-change'),
    'api_base_url' => env('XMCP_API_BASE_URL'),
    'access_contact' => env('XMCP_ACCESS_CONTACT'),
    'expected_partner_contract_version' => env('XMCP_EXPECTED_PARTNER_CONTRACT_VERSION'),
    'expected_partner_contract_sha256' => env('XMCP_EXPECTED_PARTNER_CONTRACT_SHA256'),
    'connect_timeout_seconds' => (int) env('XMCP_CONNECT_TIMEOUT', 5),
    'request_timeout_seconds' => (int) env('XMCP_REQUEST_TIMEOUT', 30),
    'rate_limit_per_minute' => (int) env('XMCP_RATE_LIMIT_PER_MINUTE', 30),
    'read_retry_delays_ms' => [100, 250],
    'public_issuance' => [
        'enabled' => env('XMCP_PUBLIC_ISSUANCE_ENABLED', false),
        'discovery_enabled' => env('XMCP_PUBLIC_ISSUANCE_DISCOVERY_ENABLED', true),
        'endpoint' => env('XMCP_PUBLIC_ISSUANCE_ENDPOINT', '/mcp/x-change/public'),
        'api_base_url' => env('XMCP_PUBLIC_ISSUANCE_API_BASE_URL'),
        'rate_limit_per_minute' => (int) env('XMCP_PUBLIC_ISSUANCE_RATE_LIMIT_PER_MINUTE', 30),
    ],
];
