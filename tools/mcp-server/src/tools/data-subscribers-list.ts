import type { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import type { Config } from "../config.js";
import { CompanionClient } from "../clients/companion.js";
import { runHandler } from "./register.js";

interface CompanionSubscriber {
  id: string;
  email: string;
  first_name: string | null;
  last_name: string | null;
  status: string;
  created_at: string;
  updated_at: string;
  source: string | null;
  segments: { id: string; name: string }[];
}

interface CompanionSubscribersResponse {
  items: CompanionSubscriber[];
  total: number;
  limit: number;
  offset: number;
}

export function registerDataSubscribersList(server: McpServer, config: Config): void {
  const companion = new CompanionClient(config);

  server.registerTool(
    "mp.data.subscribers.list",
    {
      title: "List MailPoet subscribers",
      description:
        "Lists MailPoet subscribers via the dev companion mu-plugin. Uses MailPoet's own SubscribersRepository, so results reflect real entity state (including segment memberships). Requires wp-env running and the companion mu-plugin active.",
      inputSchema: {
        email_contains: z.string().optional().describe("Case-insensitive substring match on email."),
        status: z
          .enum(["subscribed", "unsubscribed", "unconfirmed", "bounced", "inactive"])
          .optional()
          .describe("Filter by subscriber status."),
        segment_id: z.string().optional().describe("Only subscribers belonging to this segment (numeric id as string)."),
        limit: z.number().int().min(1).max(500).optional().describe("Max results (default 50)."),
        offset: z.number().int().min(0).optional().describe("Offset into the result set (default 0)."),
      },
      annotations: { readOnlyHint: true, openWorldHint: true },
    },
    async (args) =>
      runHandler("mp.data.subscribers.list", args, async () => {
        return await companion.request<CompanionSubscribersResponse>("subscribers", {
          query: {
            email_contains: args.email_contains,
            status: args.status,
            segment_id: args.segment_id,
            limit: args.limit ?? 50,
            offset: args.offset ?? 0,
          },
        });
      }),
  );
}
