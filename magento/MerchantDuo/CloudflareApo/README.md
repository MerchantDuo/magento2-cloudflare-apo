# MerchantDuo Cloudflare APO

`MerchantDuo_CloudflareApo` connects Magento cache invalidation to the v3
Cloudflare Worker in this repository. It does three things:

- reads per-website cache and purge settings;
- generates the Worker `project-config.ts` artifact from those settings;
- queues and delivers signed tag or full-flush purge requests after Magento
  cache-clean events.

It does not deploy a Worker, manage Cloudflare credentials, provide an
operations page, or translate VCL or AI-generated rules.

## Configuration

In Stores > Configuration > Services > Cloudflare APO v3, set the origin host,
protocol, cache TTL and stale period. To enable purge delivery for a website,
also set its HTTPS Worker URL, purge path, and signing secret. Delivery is off
by default. When it is off, the module neither queues nor sends purges.

The Worker must use the same signing secret as its `PURGE_SECRET` binding. The
default purge path is `/__fpc/purge`.

## Generated configuration

Run:

```sh
bin/magento cloudflare-apo:worker:build
```

The command writes a deterministic, data-only
`src/generated/project-config.ts` and `build-report.json` below
`var/merchantduo-cloudflare-apo/<sha256>/`. Copy the generated configuration
into the Worker release deliberately. This command does not build or upload a
Worker bundle.

## Purges

The module observes Magento cache-clean events and creates queue rows for each
enabled website. Its cron job runs every two minutes. You can run the delivery
worker manually:

```sh
bin/magento cloudflare-apo:cache:purge
```

Purge payloads contain up to 100 normalized cache tags, or an explicit full
flush. Each request has a timestamp, a new random nonce, and an HMAC-SHA-256
signature. Delivery retries transient HTTP failures and stops after eight
attempts. Failed rows remain in the declarative
`merchantduo_cloudflare_apo_purge_queue` table for inspection.

## Installation and validation

Place this module at `app/code/MerchantDuo/CloudflareApo`, then run Magento's
normal module setup and compilation commands in the target installation. The
repository has not yet exercised those steps in a real Magento environment.

The Worker contract is in the repository root. Read its
[README](../../../README.md) before enabling purge delivery.
