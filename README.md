# X-MCP

`3neti/x-mcp` exposes AI-native tools for the X-Change Partner API. It is a transport adapter, never an alternate financial authority.

## Boundary

Every tool calls the configured Partner API over authenticated HTTP. This package does not depend on `3neti/x-change` and cannot call its Actions, models, Treasury services, provider adapters, or legacy lifecycle API.

## Installation

```bash
composer require 3neti/x-mcp
```

```env
XMCP_ENABLED=true
XMCP_PUBLIC_DISCOVERY_ENABLED=true
XMCP_API_BASE_URL=https://your-host.example/api/partner/v1
XMCP_ACCESS_CONTACT=api-access@example.test
XMCP_EXPECTED_PARTNER_CONTRACT_VERSION=1.0.0
```

Confirm local commissioning before connecting an AI client:

```bash
php artisan x-mcp:doctor
```

The MCP client sends the same short-lived OAuth client-credentials bearer token used by the Partner API to `/mcp/x-change`. The token must include `capabilities:read` plus the scopes required by the tools it will use.

## Initial tools

- `inspect_capabilities`
- `estimate_pay_code`
- `issue_pay_code`
- `get_pay_code`
- `cancel_pay_code`

Issuance and cancellation require explicit confirmation and caller-supplied stable idempotency keys. Partner API ownership, funds, Treasury, pricing, scope, and mandate controls remain authoritative.

There is no self-service OAuth client registration.

Public discovery is available at `/.well-known/x-change-mcp`. It exposes only the
endpoint, authentication model, tool names, and access-contact policy—never client
credentials, bearer tokens, account identity, Treasury data, or claim evidence.

## Public On-Demand Issuance

An optional, separate server at `/mcp/x-change/public` exposes only anonymous,
read-only discovery, estimation, and browser-handoff tools. It never creates a
funding order, accepts payment, reserves funds, issues a Pay Code, or returns a
possession credential.

```env
XMCP_PUBLIC_ISSUANCE_ENABLED=true
XMCP_PUBLIC_ISSUANCE_API_BASE_URL=https://your-host.example/api/x/v1/public-issuance
```

Its discovery document is `/.well-known/x-change-public-mcp`. Native financial
mutations remain unavailable until a separately governed guest authorization
contract exists.
