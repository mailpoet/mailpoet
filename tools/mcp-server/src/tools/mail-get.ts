import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { z } from 'zod';
import type { Config } from '../config.js';
import { MailpitClient } from '../clients/mailpit.js';
import { runHandler } from './register.js';

export function registerMailGet(server: McpServer, config: Config): void {
  const mailpit = new MailpitClient(config);

  server.registerTool(
    'mp.mail.get',
    {
      title: 'Get a captured email by id (Mailpit)',
      description:
        'Fetches full details of a single Mailpit message: headers, text body, HTML body, and attachment metadata (without binary content). Use mp.mail.list first to get the id.',
      inputSchema: {
        id: z
          .string()
          .min(1)
          .describe('Mailpit message ID (from mp.mail.list items[].id).'),
      },
      annotations: { readOnlyHint: true, openWorldHint: true },
    },
    async (args) =>
      runHandler('mp.mail.get', args, async () => {
        const m = await mailpit.getMessage(args.id);
        return {
          id: m.ID,
          message_id: m.MessageID,
          from: { name: m.From?.Name ?? null, address: m.From?.Address ?? '' },
          to: (m.To ?? []).map((t) => ({
            name: t.Name || null,
            address: t.Address,
          })),
          cc: (m.Cc ?? []).map((t) => ({
            name: t.Name || null,
            address: t.Address,
          })),
          bcc: (m.Bcc ?? []).map((t) => ({
            name: t.Name || null,
            address: t.Address,
          })),
          subject: m.Subject,
          date: m.Date,
          size_bytes: m.Size,
          text: m.Text || null,
          html: m.HTML || null,
          attachments: (m.Attachments ?? []).map((a) => ({
            filename: a.FileName,
            content_type: a.ContentType,
            size_bytes: a.Size,
            part_id: a.PartID,
          })),
          headers: m.Headers ?? null,
        };
      }),
  );
}
