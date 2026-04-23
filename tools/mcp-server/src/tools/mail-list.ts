import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { z } from 'zod';
import type { Config } from '../config.js';
import { MailpitClient } from '../clients/mailpit.js';
import { runHandler } from './register.js';

export function registerMailList(server: McpServer, config: Config): void {
  const mailpit = new MailpitClient(config);

  server.registerTool(
    'mp.mail.list',
    {
      title: 'List captured emails (Mailpit)',
      description:
        'Lists emails captured by the local Mailpit SMTP catcher. Returns summaries (no body). Use an optional search query to filter by subject/from/to/body (Mailpit search syntax).',
      inputSchema: {
        query: z
          .string()
          .optional()
          .describe(
            "Mailpit search query (e.g. 'to:alice@example.com', 'subject:welcome').",
          ),
        limit: z
          .number()
          .int()
          .min(1)
          .max(200)
          .optional()
          .describe('Max results (default 50).'),
        start: z
          .number()
          .int()
          .min(0)
          .optional()
          .describe('Offset into the result set.'),
      },
      annotations: { readOnlyHint: true, openWorldHint: true },
    },
    async (args) =>
      runHandler('mp.mail.list', args, async () => {
        const limit = args.limit ?? 50;
        const start = args.start ?? 0;
        const response = await mailpit.listMessages({
          query: args.query,
          limit,
          start,
        });

        const items = response.messages.map((m) => ({
          id: m.ID,
          message_id: m.MessageID,
          from: { name: m.From?.Name ?? null, address: m.From?.Address ?? '' },
          to: (m.To ?? []).map((t) => ({
            name: t.Name || null,
            address: t.Address,
          })),
          subject: m.Subject,
          received_at: m.Created,
          size_bytes: m.Size,
          attachments: m.Attachments,
          snippet: m.Snippet ?? null,
        }));

        return {
          items,
          start,
          limit,
          total: response.total,
          unread: response.unread,
        };
      }),
  );
}
