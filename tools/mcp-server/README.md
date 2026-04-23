# MailPoet Dev MCP Server (experimental)

An [MCP](https://modelcontextprotocol.io) server that exposes MailPoet local-dev tooling to AI agents (Claude Code, Claude Desktop, etc.).

**Status:** experimental.

## Architecture

```
┌────────────────────┐     stdio     ┌─────────────────────┐
│   Claude / agent   │ ───────────▶  │  TS MCP server      │
└────────────────────┘               │  (this package)     │
                                     └──────┬──────────────┘
                                            │ shell-out (./do, docker)
                                            │ HTTP (Mailpit, companion)
                              ┌─────────────┼─────────────────────┐
                              ▼             ▼                     ▼
                         mailpoet/       Mailpit :8082      WordPress :8888
                         (tests, QA)     (captured mail)    + companion mu-plugin
                                                            (DI → repositories)
```

- **TS server** on the host — MCP protocol, shell-outs, Mailpit calls.
- **PHP companion mu-plugin** in `.wp-env/mu-plugins/mailpoet-dev-companion.php` — dev-only REST endpoints under `/wp-json/mailpoet-dev/v1/` backed by MailPoet's DI container and Doctrine repositories.
- **Shared secret** at `.wp-env/.mailpoet-dev-companion-secret` (gitignored). TS server generates it; `.wp-env.json` maps it into the container; mu-plugin reads it for request auth.

## Tools

| Tool                                            | Purpose                                                                                                            |
| ----------------------------------------------- | ------------------------------------------------------------------------------------------------------------------ |
| `mp.env.status`                                 | wp-env / Mailpit / companion reachability + plugin/WP/PHP versions                                                 |
| `mp.env.feature_flags.list` / `.set`            | Read/toggle experimental feature flags                                                                             |
| `mp.env.migrations.status`                      | MailPoet db+app migration state                                                                                    |
| `mp.env.scheduler.list`                         | Action Scheduler actions (filter by status/hook/group)                                                             |
| `mp.test.run`                                   | Codeception unit or integration, returns structured failures                                                       |
| `mp.mail.list` / `.get` / `.clear`              | Mailpit: list summaries, get full message, clear mailbox (destructive, confirm=true)                               |
| `mp.data.subscribers.list` / `.get` / `.create` | Subscribers CRUD via SubscribersRepository                                                                         |
| `mp.data.segments.list`                         | Segments + optional subscribed-counts                                                                              |
| `mp.qa.run`                                     | phpstan / phpcs / eslint / stylelint / prettier / tsc — flat structured violations, default scope=changed-vs-trunk |
| `mp.logs.wp_debug`                              | Tail WP debug.log, filter by level/grep                                                                            |

## Telemetry

Every tool call is appended to `.wp-env/mcp-usage.jsonl` as a single-line JSON entry:

```json
{
  "ts": "2026-04-23T09:48:25.701Z",
  "tool": "mp.data.segments.list",
  "duration_ms": 137,
  "status": "ok",
  "input_keys": ["include_counts"]
}
```

Fields: `ts` (UTC ISO8601), `tool`, `duration_ms`, `status` (`ok` or `error`), `error_code` (on error only), `input_keys` (argument names only — values are not logged). Override path via `MAILPOET_MCP_TELEMETRY_LOG` env var.

Quick analysis:

```bash
# top tools by count
cat .wp-env/mcp-usage.jsonl | jq -r '.tool' | sort | uniq -c | sort -rn

# average duration per tool
cat .wp-env/mcp-usage.jsonl | jq -s 'group_by(.tool)[] | {tool: .[0].tool, n: length, avg_ms: (map(.duration_ms)|add/length|round)}'

# recent errors
cat .wp-env/mcp-usage.jsonl | jq 'select(.status=="error")' | tail -20
```

## Configuration

URLs are resolved in priority order:

1. **Env vars** (highest): `MAILPOET_MCP_WP_URL`, `MAILPOET_MCP_MAILPIT_URL`
2. **`.wp-env.override.json`** `config.WP_HOME` / `config.WP_SITEURL` → WordPress URL
3. **`.wp-env.json`** `config.WP_HOME` / `port` → fallback
4. **Hardcoded defaults**: `http://localhost:8888`, `http://localhost:8082`

For custom hosts (e.g. `http://mailpoet.local/`), the server auto-detects from `.wp-env.override.json` — no extra setup needed.

## Setup

From repo root:

```bash
# 1. Install + build the MCP server
cd tools/mcp-server
pnpm install
pnpm build

# 2. Generate the companion secret (must exist BEFORE wp-env starts, so the mapping picks it up)
pnpm init-secret
# → writes .wp-env/.mailpoet-dev-companion-secret

# 3. Start wp-env (from repo root)
cd ../..
pnpm env:start       # or env:restart if already running
```

## Running the server

For stdio (Claude Code / Claude Desktop), point the client at:

```
node /absolute/path/to/mailpoet-dev/tools/mcp-server/dist/index.js
```

For Claude Code, the easiest way is:

```bash
claude mcp add mailpoet-dev -- node /absolute/path/to/mailpoet-dev/tools/mcp-server/dist/index.js
```

Then in a Claude Code session call `mp.env.status` to verify end-to-end.

## Manual smoke test (no MCP client needed)

```bash
# Check companion from the host
SECRET=$(cat .wp-env/.mailpoet-dev-companion-secret)
curl -s -H "X-MailPoet-Dev-Secret: $SECRET" \
  http://localhost:8888/wp-json/mailpoet-dev/v1/ping | jq

# List subscribers
curl -s -H "X-MailPoet-Dev-Secret: $SECRET" \
  "http://localhost:8888/wp-json/mailpoet-dev/v1/subscribers?limit=5" | jq
```

## Safety

- Mu-plugin is a no-op unless **both** `WP_DEBUG=true` and the secret file exist. It's mapped in by `.wp-env.json` (dev-only), not part of the MailPoet plugin release artifact.
- Shared secret uses `hash_equals` for constant-time compare.
- Secret file is `0600` on host, gitignored, regenerated if deleted.

## Development

```bash
pnpm dev          # tsc --watch
pnpm typecheck    # tsc --noEmit
```

## Adding a new tool

Conventions:

- Name: `mp.<domain>.<resource>.<verb>` (e.g. `mp.data.newsletters.list`). Domain is one of: `env`, `test`, `mail`, `data`, `qa`, `logs`.
- Read-only by default. Writes get `readOnlyHint: false`; destructive writes also get `destructiveHint: true` and a confirm gate.
- JSON-in, JSON-out. No passthrough text blobs when structure is possible.
- `list` returns compact core + must-have relations; `get` returns full detail. `list` output has `items[]` and `total`.
- Timestamps: ISO 8601 UTC strings.
- Telemetry is automatic via `runHandler` — nothing extra to wire. **Keep your handler body inside `runHandler(name, args, async () => { ... })`**; any throw that escapes `runHandler` (e.g. a try/catch at the outer layer) will not be recorded.

### Host-only tool (no MailPoet DI needed)

Use this pattern when the data source is the filesystem, a process, or an HTTP service you can reach from the host (Mailpit, a binary under `./tasks/`, git).

1. Create `src/tools/<domain>-<name>.ts`:

   ```ts
   import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
   import { z } from 'zod';
   import type { Config } from '../config.js';
   import { runHandler } from './register.js';

   export function registerMyTool(server: McpServer, config: Config): void {
     server.registerTool(
       'mp.<domain>.<name>',
       {
         title: 'Short human title',
         description: 'What it does + when to use it + important caveats.',
         inputSchema: {
           foo: z.string().describe('Describe every argument.'),
         },
         annotations: { readOnlyHint: true, openWorldHint: true },
       },
       async (args) =>
         runHandler('mp.<domain>.<name>', args, async () => {
           // Do the work. Throw `new ToolError(code, message, data)` for
           // well-formed errors; anything else becomes `error.code = "unknown"`.
           return { items: [], total: 0 };
         }),
     );
   }
   ```

2. Wire it in `src/index.ts`:

   ```ts
   import { registerMyTool } from './tools/<domain>-<name>.js';
   // ...
   registerMyTool(server, config);
   ```

3. `pnpm build`, restart the MCP server in your client.

### Tool that needs MailPoet's DI container or Doctrine

Add a companion REST endpoint in `.wp-env/mu-plugins/mailpoet-dev-companion.php`, then a thin TS tool that forwards to it.

1. In the mu-plugin, register a route inside the existing `rest_api_init` callback:

   ```php
   register_rest_route($namespace, '/my-resource', [
       'methods' => 'GET',                         // or 'POST'
       'permission_callback' => $permission,      // reuse the shared secret check
       'callback' => 'mailpoet_dev_companion_my_handler',
       'args' => [ /* WP REST schema */ ],
   ]);
   ```

2. Implement the handler as a top-level function. Always fetch services through `mailpoet_dev_companion_container()`. Mirror existing patterns — reuse `mailpoet_dev_companion_serialize_subscriber()` style helpers if the same shape already appears elsewhere.

3. Use `$container->get(SomeRepository::class)` to pull in repositories. Public services only (Symfony DI inlines private ones — if you hit `"service ... has been removed or inlined"`, pick a public class that already wraps it, or use `EntityManager` directly).

4. Add the TS tool as above, but use `CompanionClient`:

   ```ts
   import { CompanionClient } from '../clients/companion.js';
   const companion = new CompanionClient(config);
   // GET
   await companion.request('my-resource', { query: { foo: args.foo } });
   // POST
   await companion.request('my-resource', {
     method: 'POST',
     body: { foo: args.foo },
   });
   ```

5. `pnpm build` + (if mu-plugin changed) the next HTTP request picks up the new PHP. No wp-env restart needed unless you changed `.wp-env.json` mappings.

### Testing locally

Fastest smoke test without a real MCP client:

```bash
(
  printf '%s\n' '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"smoke","version":"0"}}}'
  printf '%s\n' '{"jsonrpc":"2.0","method":"notifications/initialized"}'
  printf '%s\n' '{"jsonrpc":"2.0","id":2,"method":"tools/call","params":{"name":"mp.<your.tool>","arguments":{}}}'
  sleep 1
) | node dist/index.js 2>/dev/null | tail -1 | jq
```

Companion endpoints can be hit directly with curl — see the "Manual smoke test" section above.

### Gotchas

- **Private DI services.** Calling `$container->get(SomeInternalService::class)` throws if it's not public. Either fetch a public wrapper or use static methods / `EntityManager` directly.
- **SubscriberEntity `source`** has an allowlist (api/form/unknown/imported/administrator/wordpress_user/woocommerce_user/woocommerce_checkout) — `setSource()` throws on others.
- **Action Scheduler** — MailPoet groups include `mailpoet-cron` and `mailpoet-automation`. Use `as_get_scheduled_actions()` / `ActionScheduler_Store::instance()`.
- **Test runs** — always delete `tests/_output/report.xml` before spawning Codeception, otherwise a failed run will read stale JUnit data.
- **Don't log argument values** in telemetry — `runHandler` records only input keys. If you add a new logging surface, preserve that invariant.
