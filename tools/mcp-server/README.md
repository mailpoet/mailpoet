# MailPoet Dev MCP Server (experimental)

An [MCP](https://modelcontextprotocol.io) server that exposes MailPoet local-dev tooling to AI agents (Claude Code, Claude Desktop, etc.).

**Status:** experimental walking skeleton. Only four tools implemented so far.

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

| Tool | Purpose |
|---|---|
| `mp.env.status` | wp-env / Mailpit / companion reachability + plugin/WP/PHP versions |
| `mp.env.feature_flags.list` / `.set` | Read/toggle experimental feature flags |
| `mp.env.migrations.status` | MailPoet db+app migration state |
| `mp.env.scheduler.list` | Action Scheduler actions (filter by status/hook/group) |
| `mp.test.run` | Codeception unit or integration, returns structured failures |
| `mp.mail.list` / `.get` / `.clear` | Mailpit: list summaries, get full message, clear mailbox (destructive, confirm=true) |
| `mp.data.subscribers.list` / `.get` / `.create` | Subscribers CRUD via SubscribersRepository |
| `mp.data.segments.list` | Segments + optional subscribed-counts |
| `mp.qa.run` | phpstan / phpcs / eslint / stylelint / prettier / tsc — flat structured violations, default scope=changed-vs-trunk |
| `mp.logs.wp_debug` | Tail WP debug.log, filter by level/grep |

## Telemetry

Every tool call is appended to `.wp-env/mcp-usage.jsonl` as a single-line JSON entry:

```json
{"ts":"2026-04-23T09:48:25.701Z","tool":"mp.data.segments.list","duration_ms":137,"status":"ok","input_keys":["include_counts"]}
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
