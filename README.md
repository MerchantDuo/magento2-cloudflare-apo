# Magento 2 Cloudflare FPC v3

Pre-launch Magento storefront full-page caching for Cloudflare Workers Caching.

The Worker has two entrypoints. `Gateway` is not cached: it validates purge
requests, normalizes browser requests, and bypasses private traffic. Public
`GET` and `HEAD` requests are then sent to the cache-enabled `Storefront`
entrypoint with a canonical cache key. On a native cache hit, `Storefront` does
not execute. On a miss or revalidation, it fetches Magento and only returns a
response to cache after the cache policy accepts it.

This is a v3 rewrite. It has no KV page storage, serialized page records,
hit-for-pass records, or legacy configuration aliases.

## Status

The Worker policy, native purge handling, and Magento module are implemented
locally. They have not yet been accepted against a staged Worker or a real
Magento installation. The repository does not provide Cloudflare deployment,
credential diagnostics, VCL/rule conversion, AI configuration, or an
operations UI.

## Local development

Wrangler 4.107 or later is required for per-entrypoint Workers Caching.

```sh
npm install
cp .dev.vars.example .dev.vars
# Set PURGE_SECRET and replace the development project-config fixture.
npm run check
npm run types
npx wrangler dev --local
```

`npm run check` validates the TypeScript project. `npm run types` regenerates
Worker bindings after Wrangler changes. Local Wrangler validates code and
configuration only; it cannot confirm distributed cache hits, stale delivery,
request collapse, or entrypoint-scoped purge behavior.

## Cache and purge policy

Only public `GET` and `HEAD` requests are eligible for shared caching.
Authorization, private sessions, admin, customer, cart, checkout, REST,
static and health paths, range requests, unsafe methods, and GraphQL requests
without `X-Magento-Cache-Id` bypass it. Cacheable responses must also satisfy
the configured status and MIME policy and cannot be private, `no-store`,
`no-cache`, `Vary: *`, or set cookies.

Accepted responses use `Cache-Control: public, max-age=<ttl>,
stale-while-revalidate=<grace>`. `s-maxage` is intentionally not used because
it disables Workers Caching stale-while-revalidate behavior. Magento cache tags
and the stable `site:` and `route:` tags are normalized before emission.

Magento sends a signed JSON `POST` to the configured purge path, which defaults
to `/__fpc/purge`. The payload may contain `tags`, `pathPrefixes`, or the
explicit `{ "purgeEverything": true }` form. Requests require
`X-Purge-Timestamp`, `X-Purge-Nonce`, and `X-Purge-Signature`. The signature is
the lowercase HMAC-SHA-256 digest of `<timestamp>.<nonce>.<raw request body>`,
keyed with the `PURGE_SECRET` binding.

The nonce guard prevents replay within an isolate and clock window. It is not a
distributed replay store. Magento retries must use a fresh nonce.

## Magento module

[`magento/MerchantDuo/CloudflareApo`](magento/MerchantDuo/CloudflareApo) holds
the `MerchantDuo_CloudflareApo` module. It reads website configuration, writes
a deterministic data-only `project-config.ts` artifact, and queues signed tag
and full-flush purges. Purge delivery is disabled by default.

The module documents its configuration, commands, queue, and limits in its own
[README](magento/MerchantDuo/CloudflareApo/README.md).

See [architecture.md](architecture.md), [plan-v3.md](plan-v3.md), and
[tasks.md](tasks.md) for the design and outstanding rollout work.
