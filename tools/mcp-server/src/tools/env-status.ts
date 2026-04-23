import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import type { Config } from '../config.js';
import { CompanionClient } from '../clients/companion.js';
import { runHandler } from './register.js';

interface ProbeResult {
  reachable: boolean;
  error?: string;
}

async function probe(url: string, timeoutMs = 1500): Promise<ProbeResult> {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeoutMs);
  try {
    const res = await fetch(url, {
      signal: controller.signal,
      redirect: 'manual',
    });
    return { reachable: res.status > 0 };
  } catch (e) {
    const msg = e instanceof Error ? e.message : String(e);
    return { reachable: false, error: msg };
  } finally {
    clearTimeout(timer);
  }
}

export function registerEnvStatus(server: McpServer, config: Config): void {
  const companion = new CompanionClient(config);

  server.registerTool(
    'mp.env.status',
    {
      title: 'MailPoet env status',
      description:
        'Reports the state of the local MailPoet dev environment: wp-env (WordPress at :8888), Mailpit (:8082), and the MailPoet Dev Companion mu-plugin. Includes plugin/WP/PHP versions when the companion is reachable.',
      inputSchema: {},
      annotations: { readOnlyHint: true, openWorldHint: true },
    },
    async () =>
      runHandler('mp.env.status', {}, async () => {
        const [wp, mailpit] = await Promise.all([
          probe(config.wpBaseUrl),
          probe(config.mailpitUrl),
        ]);

        let companionReachable = false;
        let companionError: string | null = null;
        let versions: Record<string, string | null> | null = null;
        try {
          const info = await companion.request<{
            plugin_version: string | null;
            wp_version: string | null;
            php_version: string | null;
            premium_active: boolean;
          }>('ping');
          companionReachable = true;
          versions = {
            plugin: info.plugin_version,
            wp: info.wp_version,
            php: info.php_version,
            premium: info.premium_active ? 'active' : 'inactive',
          };
        } catch (e) {
          companionError = e instanceof Error ? e.message : String(e);
        }

        return {
          wp_env: {
            running: wp.reachable,
            url: config.wpBaseUrl,
            ...(wp.error ? { error: wp.error } : {}),
          },
          mailpit: {
            running: mailpit.reachable,
            url: config.mailpitUrl,
            ...(mailpit.error ? { error: mailpit.error } : {}),
          },
          companion: {
            reachable: companionReachable,
            url: config.companionBaseUrl,
            secret_path: config.companionSecretPath,
            ...(companionError ? { error: companionError } : {}),
          },
          versions,
        };
      }),
  );
}
