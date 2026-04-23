import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { z } from 'zod';
import type { Config } from '../config.js';
import { exec } from '../util/exec.js';
import { ToolError } from '../util/errors.js';
import { runHandler } from './register.js';

interface LogEntry {
  raw: string;
  timestamp: string | null;
  level: string | null;
  message: string;
}

// [23-Apr-2026 09:30:36 UTC] PHP Notice:  Some message...
const LINE_RE = /^\[([^\]]+)\]\s+(PHP [^:]+):\s+(.*)$/;

function parseLine(line: string): LogEntry {
  const m = LINE_RE.exec(line);
  if (!m) return { raw: line, timestamp: null, level: null, message: line };
  return {
    raw: line,
    timestamp: m[1] ?? null,
    level: m[2] ?? null,
    message: m[3] ?? '',
  };
}

export function registerLogsWpDebug(server: McpServer, config: Config): void {
  server.registerTool(
    'mp.logs.wp_debug',
    {
      title: 'Tail WordPress debug.log',
      description:
        'Reads wp-content/debug.log from inside the wp-env cli container via `wp-env run cli`. Returns structured entries. Filters: tail (last N lines), level (PHP Notice / Warning / Fatal / Deprecated), grep (substring match on full line before parsing).',
      inputSchema: {
        tail: z
          .number()
          .int()
          .min(1)
          .max(5000)
          .optional()
          .describe('Read last N lines of the log (default 200).'),
        level: z
          .string()
          .optional()
          .describe(
            "Filter entries whose level contains this string (case-insensitive), e.g. 'Fatal', 'Notice'.",
          ),
        grep: z
          .string()
          .optional()
          .describe('Substring filter applied to the raw line before parsing.'),
      },
      annotations: { readOnlyHint: true, openWorldHint: true },
    },
    async (args) =>
      runHandler('mp.logs.wp_debug', args, async () => {
        const tail = args.tail ?? 200;
        const res = await exec(
          'npx',
          [
            'wp-env',
            'run',
            'cli',
            'bash',
            '-lc',
            `tail -n ${tail} /var/www/html/wp-content/debug.log 2>/dev/null || true`,
          ],
          { cwd: config.repoRoot, timeoutMs: 30_000 },
        );

        if (res.exitCode !== 0 && !res.stdout) {
          throw new ToolError(
            'wp_env_unavailable',
            `wp-env run failed: ${res.stderr.trim() || 'exit ' + res.exitCode}`,
          );
        }

        // wp-env run wraps output with its own noise; strip leading lines that don't look like debug.log entries.
        // The debug.log itself is at the tail. We split on newlines and keep them all — caller can filter with grep.
        const allLines = res.stdout.split('\n').filter((l) => l !== '');

        const grep = args.grep ? args.grep.toLowerCase() : null;
        const levelFilter = args.level ? args.level.toLowerCase() : null;

        const entries: LogEntry[] = [];
        for (const line of allLines) {
          if (grep && !line.toLowerCase().includes(grep)) continue;
          const parsed = parseLine(line);
          if (
            levelFilter &&
            !(parsed.level ?? '').toLowerCase().includes(levelFilter)
          )
            continue;
          entries.push(parsed);
        }

        return {
          tail_requested: tail,
          entries_returned: entries.length,
          entries,
          note:
            entries.length === 0
              ? "No matching entries. The log may be empty, or wp-env not running, or the file doesn't exist yet."
              : undefined,
        };
      }),
  );
}
