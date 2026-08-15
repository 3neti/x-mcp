<?php

declare(strict_types=1);

namespace LBHurtado\XMcp\Console\Commands;

use Illuminate\Console\Command;

class XMcpDoctorCommand extends Command
{
    protected $signature = 'x-mcp:doctor {--json : Emit a machine-readable readiness report}';

    protected $description = 'Inspect the local X-MCP configuration without exposing credentials';

    public function handle(): int
    {
        $checks = $this->checks();
        $ready = collect($checks)->every(fn (array $check): bool => $check['ready']);

        if ((bool) $this->option('json')) {
            $this->line((string) json_encode([
                'schema' => 'x-mcp.doctor.v1',
                'ready' => $ready,
                'checks' => $checks,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $ready ? self::SUCCESS : self::FAILURE;
        }

        $this->components->info('X-MCP doctor');

        foreach ($checks as $check) {
            if ($check['ready']) {
                $this->components->info($check['label'].': '.$check['message']);
            } else {
                $this->components->error($check['label'].': '.$check['message']);
            }
        }

        return $ready ? self::SUCCESS : self::FAILURE;
    }

    /** @return list<array{key: string, label: string, ready: bool, message: string}> */
    protected function checks(): array
    {
        $enabled = (bool) config('x-mcp.enabled', false);
        $apiBaseUrl = trim((string) config('x-mcp.api_base_url'));
        $contractVersion = trim((string) config('x-mcp.expected_partner_contract_version'));
        $endpoint = trim((string) config('x-mcp.endpoint'));

        return [
            [
                'key' => 'enabled',
                'label' => 'MCP endpoint',
                'ready' => $enabled,
                'message' => $enabled ? 'enabled' : 'disabled; set XMCP_ENABLED=true when this deployment should serve MCP',
            ],
            [
                'key' => 'partner_api',
                'label' => 'Partner API',
                'ready' => filter_var($apiBaseUrl, FILTER_VALIDATE_URL) !== false
                    && in_array(parse_url($apiBaseUrl, PHP_URL_SCHEME), ['http', 'https'], true),
                'message' => $apiBaseUrl !== ''
                    ? 'base URL is configured'
                    : 'XMCP_API_BASE_URL is missing',
            ],
            [
                'key' => 'contract',
                'label' => 'Partner contract',
                'ready' => $contractVersion !== '',
                'message' => $contractVersion !== ''
                    ? 'expects version '.$contractVersion
                    : 'XMCP_EXPECTED_PARTNER_CONTRACT_VERSION is missing',
            ],
            [
                'key' => 'route',
                'label' => 'MCP route',
                'ready' => str_starts_with($endpoint, '/'),
                'message' => str_starts_with($endpoint, '/')
                    ? 'registered at '.$endpoint
                    : 'XMCP_ENDPOINT must be an absolute application path',
            ],
        ];
    }
}
