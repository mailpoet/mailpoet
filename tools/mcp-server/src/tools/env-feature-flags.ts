import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { z } from 'zod';
import type { Config } from '../config.js';
import { CompanionClient } from '../clients/companion.js';
import { runHandler } from './register.js';

export function registerEnvFeatureFlags(
  server: McpServer,
  config: Config,
): void {
  const companion = new CompanionClient(config);

  server.registerTool(
    'mp.env.feature_flags.list',
    {
      title: 'List MailPoet feature flags',
      description:
        'Lists all known feature flags with their current state (enabled/default). Flags are registered in FeaturesController::$defaults.',
      inputSchema: {},
      annotations: { readOnlyHint: true, openWorldHint: true },
    },
    async () =>
      runHandler('mp.env.feature_flags.list', {}, async () => {
        return await companion.request('feature-flags');
      }),
  );

  server.registerTool(
    'mp.env.feature_flags.set',
    {
      title: 'Toggle a MailPoet feature flag',
      description:
        'Enables or disables a feature flag by name. Only flags registered in FeaturesController::$defaults can be toggled. Equivalent to going to /admin.php?page=mailpoet-experimental in wp-admin.',
      inputSchema: {
        name: z
          .string()
          .describe("Flag name (e.g. 'brand_templates', 'birthday_emails')."),
        value: z.boolean().describe('true to enable, false to disable.'),
      },
      annotations: {
        readOnlyHint: false,
        destructiveHint: true,
        idempotentHint: true,
        openWorldHint: true,
      },
    },
    async (args) =>
      runHandler('mp.env.feature_flags.set', args, async () => {
        return await companion.request(
          `feature-flags/${encodeURIComponent(args.name)}`,
          {
            method: 'POST',
            body: { value: args.value },
          },
        );
      }),
  );
}
