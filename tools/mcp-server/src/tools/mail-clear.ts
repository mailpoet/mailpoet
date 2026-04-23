import type { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import type { Config } from "../config.js";
import { MailpitClient } from "../clients/mailpit.js";
import { ToolError } from "../util/errors.js";
import { runHandler } from "./register.js";

export function registerMailClear(server: McpServer, config: Config): void {
  const mailpit = new MailpitClient(config);

  server.registerTool(
    "mp.mail.clear",
    {
      title: "Clear Mailpit mailbox (destructive)",
      description:
        "Deletes ALL captured emails from Mailpit. Requires confirm=true to execute — otherwise returns a safety error. Typical use: reset mailbox between test scenarios.",
      inputSchema: {
        confirm: z.literal(true).describe("Must be exactly true to actually delete. Any other value aborts."),
      },
      annotations: { readOnlyHint: false, destructiveHint: true, idempotentHint: true, openWorldHint: true },
    },
    async (args) =>
      runHandler("mp.mail.clear", args, async () => {
        if (args.confirm !== true) {
          throw new ToolError("confirmation_required", "Refusing to clear mailbox without confirm=true.");
        }
        const before = await mailpit.listMessages({ limit: 1 });
        await mailpit.deleteAll();
        return {
          cleared: true,
          deleted_count: before.total,
        };
      }),
  );
}
