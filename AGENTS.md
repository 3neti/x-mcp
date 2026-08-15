# X-MCP Agent Boundary

- The canonical package is `3neti/x-mcp` under namespace `LBHurtado\\XMcp`.
- Never import or invoke x-change Actions, models, Treasury services, execution drivers, provider adapters, or voucher internals.
- Every financial operation must cross the governed X-Change Partner API over authenticated HTTP.
- Never call the legacy `/api/x/v1` lifecycle surface.
- Never accept issuer identity or OAuth credentials as MCP tool arguments.
- Never log or return bearer tokens, client secrets, raw instructions, claim evidence, settlement envelopes, or provider payloads.
- Mutation tools require explicit confirmation and stable idempotency keys.
- Use Laravel MCP structured schemas and Pest tests with `Http::preventStrayRequests()`.
