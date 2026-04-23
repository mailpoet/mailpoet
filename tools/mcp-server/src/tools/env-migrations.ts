import type { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import type { Config } from "../config.js";
import { CompanionClient } from "../clients/companion.js";
import { runHandler } from "./register.js";

export function registerEnvMigrations(server: McpServer, config: Config): void {
  const companion = new CompanionClient(config);

  server.registerTool(
    "mp.env.migrations.status",
    {
      title: "MailPoet migrations status",
      description:
        "Lists all MailPoet migrations (db + app) with their state: new, started, completed, failed, unknown. Unknown = migration recorded in the store but no longer defined in code (renamed/removed).",
      inputSchema: {},
      annotations: { readOnlyHint: true, openWorldHint: true },
    },
    async () =>
      runHandler("mp.env.migrations.status", {}, async () => {
        return await companion.request("migrations");
      }),
  );
}
