import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { z } from 'zod';
import type { Config } from '../config.js';
import { CompanionClient } from '../clients/companion.js';
import { runHandler } from './register.js';

export function registerDataSubscribersGet(
  server: McpServer,
  config: Config,
): void {
  const companion = new CompanionClient(config);

  server.registerTool(
    'mp.data.subscribers.get',
    {
      title: 'Get a MailPoet subscriber by id',
      description:
        'Fetches a single subscriber by id with all segment memberships. Use mp.data.subscribers.list to find the id.',
      inputSchema: {
        id: z.number().int().positive().describe('Subscriber id.'),
      },
      annotations: { readOnlyHint: true, openWorldHint: true },
    },
    async (args) =>
      runHandler('mp.data.subscribers.get', args, async () => {
        return await companion.request(`subscribers/${args.id}`);
      }),
  );
}
