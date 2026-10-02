<?php

declare(strict_types=1);

namespace LBHurtado\XMcp;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Facades\Mcp;
use LBHurtado\XMcp\Console\Commands\XMcpDoctorCommand;
use LBHurtado\XMcp\Contracts\PartnerApiTransportContract;
use LBHurtado\XMcp\Contracts\PublicIssuanceApiTransportContract;
use LBHurtado\XMcp\Http\Middleware\AuthenticatePartnerBearer;
use LBHurtado\XMcp\Servers\XChangePartnerServer;
use LBHurtado\XMcp\Servers\XChangePublicIssuanceServer;
use LBHurtado\XMcp\Services\HttpPartnerApiTransport;
use LBHurtado\XMcp\Services\HttpPublicIssuanceApiTransport;
use LBHurtado\XMcp\Services\PartnerBearerTokenContext;

class XMcpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/x-mcp.php', 'x-mcp');

        $this->app->bind(PartnerApiTransportContract::class, HttpPartnerApiTransport::class);
        $this->app->bind(PublicIssuanceApiTransportContract::class, HttpPublicIssuanceApiTransport::class);
        $this->app->scoped(PartnerBearerTokenContext::class, fn (): PartnerBearerTokenContext => new PartnerBearerTokenContext);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/x-mcp.php' => config_path('x-mcp.php'),
        ], 'x-mcp-config');

        if ($this->app->runningInConsole()) {
            $this->commands([XMcpDoctorCommand::class]);
        }

        $this->registerRateLimiter();
        $this->registerDiscovery();

        if ((bool) config('x-mcp.enabled', false)) {
            Mcp::web((string) config('x-mcp.endpoint', '/mcp/x-change'), XChangePartnerServer::class)
                ->middleware([AuthenticatePartnerBearer::class, 'throttle:x-mcp']);
        }

        if ((bool) config('x-mcp.public_issuance.enabled', false)) {
            Mcp::web(
                (string) config('x-mcp.public_issuance.endpoint', '/mcp/x-change/public'),
                XChangePublicIssuanceServer::class,
            )->middleware(['throttle:x-mcp-public-issuance']);
        }
    }

    protected function registerRateLimiter(): void
    {
        RateLimiter::for('x-mcp', function (Request $request): Limit {
            $token = $request->bearerToken();
            $key = is_string($token) && $token !== ''
                ? 'token:'.hash('sha256', $token)
                : 'ip:'.$request->ip();

            return Limit::perMinute((int) config('x-mcp.rate_limit_per_minute', 30))->by($key);
        });

        RateLimiter::for('x-mcp-public-issuance', fn (Request $request): Limit => Limit::perMinute(
            (int) config('x-mcp.public_issuance.rate_limit_per_minute', 30),
        )->by('ip:'.$request->ip()));
    }

    protected function registerDiscovery(): void
    {
        if ((bool) config('x-mcp.public_discovery_enabled', true)) {
            Route::middleware('throttle:30,1')->get('/.well-known/x-change-mcp', function (): array {
                return [
                    'schema' => 'x-mcp.discovery.v1',
                    'name' => 'X-Change Partner MCP',
                    'endpoint' => url((string) config('x-mcp.endpoint', '/mcp/x-change')),
                    'authentication' => [
                        'type' => 'oauth2_client_credentials_bearer',
                        'baseline_scope' => 'capabilities:read',
                        'self_service_registration' => false,
                    ],
                    'tools' => [
                        'inspect_capabilities',
                        'estimate_pay_code',
                        'issue_pay_code',
                        'get_pay_code',
                        'cancel_pay_code',
                    ],
                    'access_contact' => config('x-mcp.access_contact'),
                ];
            })->name('x-mcp.discovery');
        }

        if ((bool) config('x-mcp.public_issuance.discovery_enabled', true)) {
            Route::middleware('throttle:30,1')->get('/.well-known/x-change-public-mcp', function (): array {
                return [
                    'schema' => 'x-mcp.public-issuance-discovery.v1',
                    'name' => 'X-Change Public Issuance MCP',
                    'endpoint' => url((string) config('x-mcp.public_issuance.endpoint', '/mcp/x-change/public')),
                    'authentication' => ['type' => 'none'],
                    'available' => (bool) config('x-mcp.public_issuance.enabled', false),
                    'read_only' => true,
                    'tools' => [
                        'discover_on_demand_issuance',
                        'estimate_on_demand_pay_code',
                        'prepare_on_demand_handoff',
                    ],
                ];
            })->name('x-mcp.public-issuance.discovery');
        }
    }
}
