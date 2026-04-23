import { randomBytes } from 'node:crypto';
import {
  existsSync,
  readFileSync,
  writeFileSync,
  chmodSync,
  mkdirSync,
} from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));

export interface Config {
  repoRoot: string;
  mailpoetDir: string;
  wpBaseUrl: string;
  mailpitUrl: string;
  companionBaseUrl: string;
  companionSecretPath: string;
  companionSecret: string;
  telemetryLogPath: string;
}

interface WpEnvFile {
  port?: number;
  config?: Record<string, unknown>;
}

function findRepoRoot(): string {
  let cur = resolve(here);
  for (let i = 0; i < 10; i++) {
    if (existsSync(resolve(cur, '.wp-env.json'))) return cur;
    const parent = dirname(cur);
    if (parent === cur) break;
    cur = parent;
  }
  throw new Error(
    'Unable to locate repo root (no .wp-env.json found walking up from mcp-server).',
  );
}

function readJsonIfExists<T>(path: string): T | null {
  if (!existsSync(path)) return null;
  try {
    return JSON.parse(readFileSync(path, 'utf8')) as T;
  } catch {
    return null;
  }
}

function stripTrailingSlash(url: string): string {
  return url.replace(/\/+$/, '');
}

function resolveWpBaseUrl(repoRoot: string): string {
  const envOverride = process.env.MAILPOET_MCP_WP_URL?.trim();
  if (envOverride) return stripTrailingSlash(envOverride);

  const override = readJsonIfExists<WpEnvFile>(
    resolve(repoRoot, '.wp-env.override.json'),
  );
  const base = readJsonIfExists<WpEnvFile>(resolve(repoRoot, '.wp-env.json'));

  // WP_HOME / WP_SITEURL in override take precedence — they reflect the real externally-visible URL.
  const home =
    (override?.config?.WP_HOME as string | undefined) ??
    (base?.config?.WP_HOME as string | undefined);
  if (typeof home === 'string' && home) return stripTrailingSlash(home);

  const port = override?.port ?? base?.port ?? 8888;
  return `http://localhost:${port}`;
}

function resolveMailpitUrl(): string {
  const envOverride = process.env.MAILPOET_MCP_MAILPIT_URL?.trim();
  if (envOverride) return stripTrailingSlash(envOverride);
  return 'http://localhost:8082';
}

function readOrGenerateSecret(path: string): string {
  if (existsSync(path)) {
    const s = readFileSync(path, 'utf8').trim();
    if (s.length >= 32) return s;
    // Never silently overwrite: wp-env bind-mounts the file at container start,
    // so regenerating on the host while the container is running would cause
    // every companion request to 403 with no obvious cause. Force the user to
    // delete the file explicitly.
    throw new Error(
      `Companion secret at ${path} is shorter than 32 chars. Delete it and re-run to regenerate (then 'pnpm env:restart' so wp-env picks up the new file).`,
    );
  }
  const secret = randomBytes(32).toString('hex');
  mkdirSync(dirname(path), { recursive: true });
  writeFileSync(path, secret + '\n', { mode: 0o600 });
  chmodSync(path, 0o600);
  return secret;
}

export function loadConfig(): Config {
  const repoRoot = findRepoRoot();
  const wpBaseUrl = resolveWpBaseUrl(repoRoot);
  const mailpitUrl = resolveMailpitUrl();
  const companionSecretPath = resolve(
    repoRoot,
    '.wp-env',
    '.mailpoet-dev-companion-secret',
  );
  const companionSecret = readOrGenerateSecret(companionSecretPath);
  const telemetryLogPath =
    process.env.MAILPOET_MCP_TELEMETRY_LOG?.trim() ||
    resolve(repoRoot, '.wp-env', 'mcp-usage.jsonl');

  return {
    repoRoot,
    mailpoetDir: resolve(repoRoot, 'mailpoet'),
    wpBaseUrl,
    mailpitUrl,
    companionBaseUrl: `${wpBaseUrl}/wp-json/mailpoet-dev/v1`,
    companionSecretPath,
    companionSecret,
    telemetryLogPath,
  };
}
