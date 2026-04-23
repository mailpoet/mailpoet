import type { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import type { Config } from "../config.js";
import { CompanionClient } from "../clients/companion.js";
import { runHandler } from "./register.js";

export function registerDataSegmentsList(server: McpServer, config: Config): void {
  const companion = new CompanionClient(config);

  server.registerTool(
    "mp.data.segments.list",
    {
      title: "List MailPoet segments",
      description:
        "Lists segments (lists + dynamic segments + WP/Woo auto-segments). Optionally includes the subscribed-count per segment.",
      inputSchema: {
        type: z.string().optional().describe("Filter by segment type (e.g. 'default', 'dynamic', 'wp_users', 'woocommerce_users')."),
        include_counts: z.boolean().optional().describe("If true, include the number of subscribed subscribers per segment. Slower."),
      },
      annotations: { readOnlyHint: true, openWorldHint: true },
    },
    async (args) =>
      runHandler("mp.data.segments.list", args, async () => {
        return await companion.request("segments", {
          query: { type: args.type, include_counts: args.include_counts ? "true" : undefined },
        });
      }),
  );
}
