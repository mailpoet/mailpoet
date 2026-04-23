import { existsSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';
import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { XMLParser } from 'fast-xml-parser';
import { z } from 'zod';
import type { Config } from '../config.js';
import { exec } from '../util/exec.js';
import { runHandler } from './register.js';

const Suite = z.enum(['unit', 'integration']);

interface TestFailure {
  test: string;
  file: string | null;
  line: number | null;
  message: string;
  trace: string;
}

interface ParsedReport {
  total: number;
  passed: number;
  failed: number;
  skipped: number;
  errors: number;
  duration_ms: number;
  failures: TestFailure[];
}

function parseJunit(xmlPath: string): ParsedReport | null {
  if (!existsSync(xmlPath)) return null;
  const xml = readFileSync(xmlPath, 'utf8');
  const parser = new XMLParser({
    ignoreAttributes: false,
    attributeNamePrefix: '@_',
    trimValues: true,
  });
  const doc = parser.parse(xml);

  const root = doc.testsuites ?? doc;
  const suites = Array.isArray(root.testsuite)
    ? root.testsuite
    : root.testsuite
    ? [root.testsuite]
    : [];

  let total = 0;
  let failed = 0;
  let skipped = 0;
  let errors = 0;
  let durationSec = 0;
  const failures: TestFailure[] = [];

  const walk = (suiteNode: Record<string, unknown>): void => {
    const n = (k: string): number => Number(suiteNode[k] ?? 0);
    total += n('@_tests');
    failed += n('@_failures');
    skipped += n('@_skipped');
    errors += n('@_errors');
    durationSec += n('@_time');

    const cases = Array.isArray(suiteNode.testcase)
      ? (suiteNode.testcase as Record<string, unknown>[])
      : suiteNode.testcase
      ? [suiteNode.testcase as Record<string, unknown>]
      : [];

    for (const tc of cases) {
      const fail = tc.failure ?? tc.error;
      if (!fail) continue;
      const f = Array.isArray(fail) ? fail[0] : fail;
      const fObj =
        typeof f === 'object' && f !== null
          ? (f as Record<string, unknown>)
          : null;
      const text =
        fObj && '#text' in fObj ? String(fObj['#text']) : String(f ?? '');
      const message =
        fObj && '@_message' in fObj
          ? String(fObj['@_message'])
          : text.split('\n')[0] ?? '';
      const clsName = String(tc['@_classname'] ?? '');
      const testName = String(tc['@_name'] ?? '');
      const fileAttr = tc['@_file'];
      const lineAttr = tc['@_line'];
      failures.push({
        test: clsName ? `${clsName}::${testName}` : testName,
        file: fileAttr ? String(fileAttr) : null,
        line: lineAttr !== undefined ? Number(lineAttr) : null,
        message,
        trace: text,
      });
    }

    const nested = Array.isArray(suiteNode.testsuite)
      ? suiteNode.testsuite
      : suiteNode.testsuite
      ? [suiteNode.testsuite]
      : [];
    for (const child of nested) walk(child as Record<string, unknown>);
  };

  for (const s of suites) walk(s as Record<string, unknown>);

  return {
    total,
    passed: Math.max(0, total - failed - errors - skipped),
    failed,
    skipped,
    errors,
    duration_ms: Math.round(durationSec * 1000),
    failures,
  };
}

export function registerTestRun(server: McpServer, config: Config): void {
  server.registerTool(
    'mp.test.run',
    {
      title: 'Run MailPoet tests',
      description:
        "Runs a MailPoet Codeception test suite and returns structured failures. Supports 'unit' (fast, on host) and 'integration' (tests_env docker stack, --skip-deps by default). Full raw output is written to a file; the response returns only structured failures plus the log path to keep tokens small.",
      inputSchema: {
        suite: Suite.describe("Test suite to run: 'unit' or 'integration'."),
        filter: z.string().optional().describe('Codeception --filter pattern.'),
        file: z
          .string()
          .optional()
          .describe(
            "Path to a specific test file, relative to mailpoet/ (e.g. 'tests/unit/WP/EmojiTest.php').",
          ),
      },
      annotations: {
        readOnlyHint: false,
        destructiveHint: false,
        idempotentHint: true,
        openWorldHint: false,
      },
    },
    async (args) =>
      runHandler('mp.test.run', args, async () => {
        const xmlPath = resolve(config.mailpoetDir, 'tests/_output/report.xml');
        const logPath = resolve(
          config.mailpoetDir,
          'tests/_output/mcp-last-run.log',
        );

        // Delete any stale JUnit report so that if Codeception never reaches the
        // point of emitting a new one (e.g. invalid file path, bootstrap error),
        // we return zeroed counts instead of the previous run's data.
        if (existsSync(xmlPath)) rmSync(xmlPath);

        const cmdArgs = [
          args.suite === 'unit' ? 'test:unit' : 'test:integration',
          '--xml',
        ];
        if (args.suite === 'integration') cmdArgs.push('--skip-deps');
        if (args.file) cmdArgs.push(`--file=${args.file}`);
        if (args.filter) cmdArgs.push(`--filter=${args.filter}`);

        const result = await exec('./do', cmdArgs, {
          cwd: config.mailpoetDir,
          timeoutMs:
            args.suite === 'integration' ? 30 * 60 * 1000 : 10 * 60 * 1000,
        });
        writeFileSync(
          logPath,
          `$ ./do ${cmdArgs.join(' ')}\n\n[exit ${
            result.exitCode
          }]\n\n# stdout\n${result.stdout}\n\n# stderr\n${result.stderr}\n`,
        );

        const report = parseJunit(xmlPath);

        return {
          suite: args.suite,
          status: result.exitCode === 0 ? 'passed' : 'failed',
          exit_code: result.exitCode,
          duration_ms: result.duration_ms,
          counts: report
            ? {
                total: report.total,
                passed: report.passed,
                failed: report.failed,
                skipped: report.skipped,
                errors: report.errors,
              }
            : null,
          failures: report?.failures ?? [],
          log_path: logPath,
          junit_xml_path: existsSync(xmlPath) ? xmlPath : null,
          note:
            report === null
              ? 'JUnit XML not found — check the log file for details. The Codeception run may have errored before emitting a report.'
              : undefined,
        };
      }),
  );
}
