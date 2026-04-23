import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { z } from 'zod';
import type { Config } from '../config.js';
import { CompanionClient } from '../clients/companion.js';
import { runHandler } from './register.js';

interface CreateResponse {
  subscriber: {
    id: string;
    email: string;
    first_name: string | null;
    last_name: string | null;
    status: string;
    created_at: string | null;
    updated_at: string | null;
    source: string | null;
    segments: { id: string; name: string }[];
  };
  created: boolean;
  upserted: boolean;
}

export function registerDataSubscribersCreate(
  server: McpServer,
  config: Config,
): void {
  const companion = new CompanionClient(config);

  server.registerTool(
    'mp.data.subscribers.create',
    {
      title: 'Create a MailPoet subscriber',
      description:
        "Creates a single MailPoet subscriber via Doctrine directly. Bypasses SubscriberSaveController, so NO confirmation email, NO welcome scheduler, and NO `mailpoet_segment_subscribed` action. Doctrine lifecycle listeners still run, so the `mailpoet_subscriber_created` / `_updated` / `_status_changed` WordPress actions WILL fire at request shutdown. Fails with 409 on duplicate email unless upsert=true. Source must be one of MailPoet's allowlist: api (default), form, unknown, imported, administrator, wordpress_user, woocommerce_user, woocommerce_checkout.",
      inputSchema: {
        email: z
          .string()
          .email()
          .describe('Subscriber email (required, will be lowercased).'),
        first_name: z.string().optional().describe('First name.'),
        last_name: z.string().optional().describe('Last name.'),
        status: z
          .enum([
            'subscribed',
            'unsubscribed',
            'unconfirmed',
            'bounced',
            'inactive',
          ])
          .optional()
          .describe(
            "Subscriber status. Defaults to 'subscribed' for new rows; existing status is preserved on upsert unless explicitly set.",
          ),
        source: z
          .enum([
            'api',
            'form',
            'unknown',
            'imported',
            'administrator',
            'wordpress_user',
            'woocommerce_user',
            'woocommerce_checkout',
          ])
          .optional()
          .describe(
            "Source tag. Must be one of MailPoet's allowed values. Defaults to 'api'.",
          ),
        segment_ids: z
          .array(z.number().int().positive())
          .optional()
          .describe(
            'Segment IDs to subscribe to. Each is added without firing `mailpoet_segment_subscribed` (no welcome emails).',
          ),
        upsert: z
          .boolean()
          .optional()
          .describe(
            'If true, update the existing row instead of failing on duplicate email. Default: false.',
          ),
      },
      annotations: {
        readOnlyHint: false,
        destructiveHint: false,
        idempotentHint: false,
        openWorldHint: true,
      },
    },
    async (args) =>
      runHandler('mp.data.subscribers.create', args, async () => {
        return await companion.request<CreateResponse>('subscribers', {
          method: 'POST',
          body: {
            email: args.email,
            first_name: args.first_name,
            last_name: args.last_name,
            status: args.status,
            source: args.source,
            segment_ids: args.segment_ids,
            upsert: args.upsert ?? false,
          },
        });
      }),
  );
}
