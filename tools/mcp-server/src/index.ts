#!/usr/bin/env node
import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import { loadConfig } from './config.js';
import { initTelemetry } from './util/telemetry.js';
import { registerEnvStatus } from './tools/env-status.js';
import { registerEnvFeatureFlags } from './tools/env-feature-flags.js';
import { registerEnvMigrations } from './tools/env-migrations.js';
import { registerEnvScheduler } from './tools/env-scheduler.js';
import { registerTestRun } from './tools/test-run.js';
import { registerMailList } from './tools/mail-list.js';
import { registerMailGet } from './tools/mail-get.js';
import { registerMailClear } from './tools/mail-clear.js';
import { registerDataSubscribersList } from './tools/data-subscribers-list.js';
import { registerDataSubscribersGet } from './tools/data-subscribers-get.js';
import { registerDataSubscribersCreate } from './tools/data-subscribers-create.js';
import { registerDataSegmentsList } from './tools/data-segments-list.js';
import { registerQaRun } from './tools/qa-run.js';
import { registerLogsWpDebug } from './tools/logs-wp-debug.js';

async function main(): Promise<void> {
  const config = loadConfig();
  initTelemetry(config.telemetryLogPath);

  const server = new McpServer({
    name: 'mailpoet-dev',
    version: '0.0.2',
  });

  registerEnvStatus(server, config);
  registerEnvFeatureFlags(server, config);
  registerEnvMigrations(server, config);
  registerEnvScheduler(server, config);
  registerTestRun(server, config);
  registerMailList(server, config);
  registerMailGet(server, config);
  registerMailClear(server, config);
  registerDataSubscribersList(server, config);
  registerDataSubscribersGet(server, config);
  registerDataSubscribersCreate(server, config);
  registerDataSegmentsList(server, config);
  registerQaRun(server, config);
  registerLogsWpDebug(server, config);

  const transport = new StdioServerTransport();
  await server.connect(transport);

  process.stderr.write(
    `[mailpoet-dev-mcp] ready (repo=${config.repoRoot}, wp=${config.wpBaseUrl}, mailpit=${config.mailpitUrl}, telemetry=${config.telemetryLogPath})\n`,
  );
}

main().catch((err) => {
  process.stderr.write(
    `[mailpoet-dev-mcp] fatal: ${
      err instanceof Error ? err.stack : String(err)
    }\n`,
  );
  process.exit(1);
});
