import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { z } from 'zod';
import type { Config } from '../config.js';
import { CompanionClient } from '../clients/companion.js';
import { runHandler } from './register.js';

export function registerEnvScheduler(server: McpServer, config: Config): void {
  const companion = new CompanionClient(config);

  server.registerTool(
    'mp.env.scheduler.list',
    {
      title: 'List Action Scheduler actions',
      description:
        'Lists scheduled actions from WordPress Action Scheduler (the engine MailPoet uses for background jobs). Filterable by status, hook substring, and group. Useful for debugging stuck cron / automation runs.',
      inputSchema: {
        status: z
          .enum(['pending', 'in-progress', 'complete', 'failed', 'canceled'])
          .optional()
          .describe('Filter by action status.'),
        hook_contains: z
          .string()
          .optional()
          .describe(
            "Only return actions whose hook name contains this substring (e.g. 'mailpoet/cron').",
          ),
        group: z
          .string()
          .optional()
          .describe(
            "Filter by Action Scheduler group (e.g. 'mailpoet-cron', 'mailpoet-automation').",
          ),
        limit: z
          .number()
          .int()
          .min(1)
          .max(500)
          .optional()
          .describe('Max results (default 50).'),
      },
      annotations: { readOnlyHint: true, openWorldHint: true },
    },
    async (args) =>
      runHandler('mp.env.scheduler.list', args, async () => {
        return await companion.request('scheduler', {
          query: {
            status: args.status,
            hook_contains: args.hook_contains,
            group: args.group,
            limit: args.limit ?? 50,
          },
        });
      }),
  );
}
